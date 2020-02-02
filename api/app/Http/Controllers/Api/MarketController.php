<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Entities\Coin;
use App\Entities\CoinPair;
use App\Entities\CurrencyRate;
use App\Entities\Order;
use App\Entities\Transaction;
use App\Http\Controllers\Controller;
use Carbon\Carbon;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class MarketController extends Controller
{
    public function getCoins(Request $request) {
        $query = Coin::query();

        $input = $request->input();

        if (isset($input['only_coin'])) {
            $query->where('is_crypto', '=', 1);
        }
        if (isset($input['only_currency'])) {
            $query->where('is_crypto', '=', 0);
        }

        $query->orderBy('rank');
        $result = $query->get();
        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function updateCurrencyRate() {
        $ch = curl_init();

        $tl_usd = 0;
        curl_setopt($ch, CURLOPT_URL, 'https://www.doviz.com/api/v1/currencies/USD/latest');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch));

        if (isset($response->buying)) {
            $tl_usd = $response->buying;
        } else {
            return;
        }

        $tl_eur = 0;
        curl_setopt($ch, CURLOPT_URL, 'https://www.doviz.com/api/v1/currencies/EUR/latest');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = json_decode(curl_exec($ch));

        if (isset($response->buying)) {
            $tl_eur = $response->buying;
        } else {
            return;
        }

        $rate = CurrencyRate::find(1);
        if ($rate) {
            $rate->usd_price = $tl_usd;
            $rate->euro_price = $tl_eur;
            $rate->save();
        }
    }
    public function getCurrencyRate(Request $request) {
        $rate = CurrencyRate::find(1);

        return response()->json([
            'success' => true,
            'USD' => $rate->usd_price,
            'EUR' => $rate->euro_price
        ]);
    }

    public function getCoinPairInfo(Request $request) {
        if ($coinpair = Redis::get('coinpair.all')) {
            return response()->json([
                'success' => true,
                'data' => json_decode($coinpair)
            ]);
        }

        $before_day = Carbon::now()->addDay(-1)->toDateTimeString();

        $marketCoin = 'TL';

        $query =    "SELECT coin_pairs.*, coins.*, IFNULL(trans.high, 0) high, IFNULL(trans.low, 0) low, ".
                            "IFNULL(first_tran.rate, 0) first_price, IFNULL(last_tran.rate, 0) last_price, IFNULL(trans.volume, 0) volume ".
                    "FROM coin_pairs LEFT JOIN (".
                        "SELECT dest_coin AS market_coin, src_coin AS coin, MAX(rate) high, MIN(rate) low, MIN(id) min_id, MAX(id) max_id, SUM(src_amount) volume ".
                            "FROM transactions ".
                            "WHERE status = ".TransactionStatus::SUCCESS." AND exchange_type = ".MarketOrderType::SELL." AND datetime >= '".$before_day."' ".
                            "GROUP BY dest_coin, src_coin ".
                            ") trans ON coin_pairs.coin = trans.coin AND coin_pairs.market_coin = trans.market_coin ".
                        "LEFT JOIN transactions AS first_tran ON first_tran.id = trans.min_id ".
                        "LEFT JOIN transactions AS last_tran ON last_tran.id = trans.max_id ".
                        "LEFT JOIN coins ON coin_pairs.coin = coins.coin ".
                    "WHERE coin_pairs.suspended = 0 ".
                    "ORDER BY coins.id";
        $result = \DB::select($query);

        foreach($result as $record) {
            $record->change_price = $record->first_price > 0 ? ($record->last_price - $record->first_price) : $record->last_price;
            $record->change = round($record->first_price > 0 ? ($record->last_price - $record->first_price) * 100 / $record->first_price : 0, 2);
        }

        Redis::setex('coinpair.all', 60 * 30, json_encode($result));

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function getUserCoinPairInfo(Request $request) {
        $locale = $request->input('locale');

        if (is_null($locale)) {
            return response()->json([
                'success' => false,
                'data' => array()
            ]);
        }

        $user = Auth::user();
        $before_day = Carbon::now()->addDay(-1)->toDateTimeString();

        $marketCoin = 'TL';

        $query =    "SELECT coin_pairs.*, coins.fullname, IFNULL(users_fee.sell_fee, coins.sell_fee) sell_fee, IFNULL(users_fee.buy_fee, coins.buy_fee) buy_fee, ".
                        "IFNULL(trans.high, 0) high, IFNULL(trans.low, 0) low, ".
                        "IFNULL(first_tran.rate, 0) first_price, IFNULL(last_tran.rate, 0) last_price, IFNULL(trans.volume, 0) volume ".
                    "FROM coin_pairs LEFT JOIN (".
                        "SELECT dest_coin AS market_coin, src_coin AS coin, MAX(rate) high, MIN(rate) low, MIN(id) min_id, MAX(id) max_id, SUM(src_amount) volume ".
                            "FROM transactions ".
                            "WHERE status = ".TransactionStatus::SUCCESS." AND exchange_type = ".MarketOrderType::SELL." AND datetime >= '".$before_day."' ".
                            "GROUP BY dest_coin, src_coin ".
                        ") trans ON coin_pairs.coin = trans.coin AND coin_pairs.market_coin = trans.market_coin ".
                        "LEFT JOIN transactions AS first_tran ON first_tran.id = trans.min_id ".
                        "LEFT JOIN transactions AS last_tran ON last_tran.id = trans.max_id ".
                        "LEFT JOIN coins ON coin_pairs.coin = coins.coin ".
                        "LEFT JOIN users_fee ON coin_pairs.market_coin = users_fee.market_coin AND coin_pairs.coin = users_fee.coin AND users_fee.user_id = ".$user->id." ".
                    "WHERE coin_pairs.suspended = 0 ".
                    "ORDER BY coins.id";
        $result = \DB::select($query);

        foreach($result as $record) {
            $record->change = round($record->first_price > 0 ? ($record->last_price - $record->first_price) * 100 / $record->first_price : 0, 2);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function getChartData($coin, $marketCoin, Request $request) {
        if ($chartdata = Redis::get('chartdata.all.'.$coin.'.'.$marketCoin)) {
            return response()->json([
                'success' => true,
                'data' => json_decode($chartdata)
            ]);
        }

        $pair = CoinPair::where([['coin', $coin], ['market_coin', $marketCoin]])->first();

        if (!$pair) {
            return response()->json([
                'success' => false,
                'error' => 'Please try again later'
            ]);
        }

        $start = Carbon::now()->addDays(-10)->toDateTimeString();

        $query =    "SELECT MIN(datetime) datetime, MAX(rate) high, MIN(rate) low, MAX(rate) open, MIN(rate) close, SUM(src_amount) volume ".
                    "FROM transactions ".
                    "WHERE src_coin = '{$coin}' AND dest_coin = '{$marketCoin}' AND status = ".TransactionStatus::SUCCESS." AND type = ".TransactionType::EXCHANGE." AND datetime >= '".$start."' ".
                    "GROUP BY DATE(datetime), HOUR(datetime), MINUTE(datetime)";

        $result = \DB::select($query);

        foreach ($result as $record) {
            $record->date = strtotime($record->datetime) * 1000;
        }

        Redis::setex('chartdata.all.'.$coin.'.'.$marketCoin, 60 * 60, json_encode($result));

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function updateDB(Request $request) {
        var_dump('success');
    }

    public function getPrice(Request $request) {
        if ($pricedata = Redis::get('price.all')) {
            return response()->json([
                'success' => true,
                'data' => json_decode($pricedata)
            ]);
        }

        $before_day = Carbon::now()->addDay(-1)->toDateTimeString();

        $marketCoin = 'TL';

        $query =    "SELECT coins.coin, trans.high, trans.low, first_tran.rate first_price, last_tran.rate last_price, trans.volume ".
                    "FROM coins LEFT JOIN (".
                            "SELECT src_coin AS coin, MAX(rate) high, MIN(rate) low, MIN(id) min_id, MAX(id) max_id, SUM(src_amount) volume ".
                            "FROM transactions ".
                            "WHERE status = ".TransactionStatus::SUCCESS." AND dest_coin = '{$marketCoin}' AND type = ".TransactionType::EXCHANGE." AND exchange_type = ".MarketOrderType::SELL." AND datetime >= '".$before_day."' ".
                            "GROUP BY src_coin ".
                        ") trans ON coins.coin = trans.coin ".
                        "LEFT JOIN transactions AS first_tran ON first_tran.id = trans.min_id ".
                        "LEFT JOIN transactions AS last_tran ON last_tran.id = trans.max_id ".
                    "WHERE coins.coin <> 'TL' ".
                    "ORDER BY coins.id";
        $result = \DB::select($query);

        $data = array();
        foreach($result as $record) {
            $change = round($record->first_price > 0 ? ($record->last_price - $record->first_price) * 100 / $record->first_price : 0, 2);
            $coin = strtolower($record->coin);
            $data[$coin] = array(
                'last_price' => $record->last_price ?: 0,
                'high' => $record->high ?: 0,
                'low' => $record->low ?: 0,
                'change' => $change ?: 0,
                'volume' => $record->volume ?: 0,
            );
        }

        Redis::setex('price.all', 60 * 60, json_encode($data));

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getTicker(Request $request) {
        if ($tickerdata = Redis::get('ticker.all')) {
            return response()->json([
                'success' => true,
                'data' => json_decode($tickerdata)
            ]);
        }

        $before_day = Carbon::now()->addDay(-1)->toDateTimeString();

//        $currency = array(
//            'TL' => 'TRY',
//            'USD' => 'USD',
//            'USDT' => 'USDT',
//            'EURO' => 'EURO',
//            'BTC' => 'BTC',
//            'ETH' => 'ETH',
//            'XRP' => 'XRP'
//        );

        $coins = array('BTC', 'ETH', 'XRP', 'LTC', 'DOGE', 'BTG', 'BCH', 'ZEC', 'DGB', 'XLM', 'FRST', 'BEN', 'XIN', 'TRX', 'WRC', 'GRS','MISC','USC');
        $query =    "SELECT coin_pairs.coin, coin_pairs.market_coin, coins.fullname, trans.high, trans.low, first_tran.rate first_price, last_tran.rate last_price, trans.volume, bid_order.bid, ask_order.ask  ".
                    "FROM coin_pairs ".
                        "LEFT JOIN coins ON coin_pairs.coin = coins.coin ".
                        "LEFT JOIN (".
                            "SELECT dest_coin AS market_coin, src_coin AS coin, MAX(rate) high, MIN(rate) low, MIN(id) min_id, MAX(id) max_id, SUM(src_amount) volume ".
                            "FROM transactions ".
                            "WHERE status = ".TransactionStatus::SUCCESS." AND type = ".TransactionType::EXCHANGE." AND exchange_type = ".MarketOrderType::SELL." AND datetime >= '".$before_day."' ".
                            "GROUP BY dest_coin, src_coin ".
                        ") trans ON coin_pairs.coin = trans.coin AND coin_pairs.market_coin = trans.market_coin ".
                        "LEFT JOIN transactions AS first_tran ON first_tran.id = trans.min_id ".
                        "LEFT JOIN transactions AS last_tran ON last_tran.id = trans.max_id ".
                        "LEFT JOIN (SELECT src_coin AS market_coin, dest_coin coin, MAX(rate) bid FROM orders GROUP BY src_coin, dest_coin) bid_order ON coin_pairs.coin = bid_order.coin AND coin_pairs.market_coin = bid_order.market_coin ".
                        "LEFT JOIN (SELECT dest_coin AS market_coin, src_coin coin, MIN(rate) ask FROM orders GROUP BY dest_coin, src_coin) ask_order ON coin_pairs.coin = ask_order.coin AND coin_pairs.market_coin = ask_order.market_coin ".
                    "WHERE coin_pairs.suspended = 0 ".
                    "ORDER BY coins.id";
        $result = \DB::select($query);

        $data = array();
        foreach($result as $record) {
            $change = round($record->first_price > 0 ? ($record->last_price - $record->first_price) * 100 / $record->first_price : 0, 2);
            $change_amount = number_format($record->last_price - $record->first_price, 8, '.', '');

            if (!isset($data[$record->coin]) || !is_array($data[$record->coin])) {
                $data[$record->coin] = array();
            }

            // if (!isset($currency[$record->market_coin])) continue;
            $marketCoin = $record->market_coin;
            if ($marketCoin == 'TL') {
                $marketCoin = 'TRY';
            }
            array_push($data[$record->coin], array(
                'short_code' => $record->coin,
                'name' => $record->fullname,
//                'currency' => $currency[$record->market_coin],
                'currency' => $marketCoin,
                'current' => $record->last_price ?: 0,
                'change_amount' => $change_amount ?: 0,
                'change_percentage' => $change ?: 0,
                'high' => $record->high ?: 0,
                'low' => $record->low ?: 0,
                'volume' => $record->volume ?: 0,
                'ask' => $record->ask ?: 0,
                'bid' => $record->bid ?: 0,
            ));
        }

//        $result = array();
//
//        foreach ($coins as $coin) {
//            $result[$coin] = $data[$coin];
//        }

        Redis::setex('ticker.all', 60 * 5, json_encode($data));

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getLastPrice($marketCoin, $coin) {
        $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', $marketCoin], ['dest_coin', $coin]])
                    ->orderBy('id', 'desc')->first();

        if ($sql) {
            return response()->json([
                'success' => true,
                'price' => $sql->rate
            ]);
        } else {
            return response()->json([
                'success' => false,
            ]);
        }
    }

    public function getMarketHistory($coin, $marketCoin, Request $request) {
        $pair = $coin.'-'.$marketCoin;

        $input = $request->input();

        $txs = Transaction::whereRaw("(CONCAT(src_coin, '-', dest_coin) = '".$pair."' OR CONCAT(dest_coin, '-', src_coin) = '".$pair."') AND type = ".TransactionType::EXCHANGE." ")
            ->leftJoin('users', 'transactions.user_id', 'users.id')
            ->orderBy('transactions.datetime', 'DESC')
            ->select(\DB::raw('transactions.*'), 'users.name')
            ->limit(500)
            ->get();
        return response()->json([
            'success' => true,
            'data' => $txs
        ]);
    }

    public function deleteMarketHistory(Request $request) {
        $id = $request->input('id');

        $tx = Transaction::find($id);

        if (!$tx || $tx->type != TransactionType::EXCHANGE) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction Not Found'
            ]);
        }

        $tx->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getOrderBook(Request $request) {
        $input = $request->input();

        $symbol = isset($input['symbol']) ? $input['symbol'] : '';
        $limit = isset($input['limit']) ? $input['limit'] : 50;

        $symbol = str_replace('TRY', 'TL', $symbol);
        if ($orderbook = Redis::get('orderbook.'.$symbol.$limit)) {
            $data = json_decode($orderbook);
            // $data->timestamp = time();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        $pair = CoinPair::whereRaw("CONCAT(coin, market_coin) = '".$symbol."'")->first();

        if (!$pair) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Symbol.'
            ]);
        }

        $limits = array(5, 10, 20, 50, 100, 500, 1000);
        if (!in_array($limit, $limits)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid limit. range is '5, 10, 20, 50, 100, 500, 1000'."
            ]);
        }

        $bids = Order::where([['src_coin', $pair->coin], ['dest_coin', $pair->market_coin]])
                    ->groupBy('rate')
                    ->orderBy('rate', 'ASC')
                    ->select('rate', \DB::raw('SUM(src_amount) AS amount'))
                    ->limit($limit)
                    ->get();

        $asks = Order::where([['src_coin', $pair->market_coin], ['dest_coin', $pair->coin]])
                    ->groupBy('rate')
                    ->orderBy('rate', 'DESC')
                    ->select('rate', \DB::raw('SUM(dest_amount) AS amount'))
                    ->limit($limit)
                    ->get();

        $data = array();
        $data['timestamp'] = time();
        $data['bids'] = array();
        foreach ($bids as $bid) {
            array_push($data['bids'], array($bid->rate, $bid->amount));
        }
        $data['asks'] = array();
        foreach ($asks as $ask) {
            array_push($data['asks'], array($ask->rate, $ask->amount));
        }

        Redis::setex('orderbook.'.$symbol.$limit, 60 * 0.5, json_encode($data));

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getTrades(Request $request) {
        $input = $request->input();

        $symbol = isset($input['symbol']) ? $input['symbol'] : '';
        $limit = isset($input['limit']) ? $input['limit'] : 50;

        $symbol = str_replace('TRY', 'TL', $symbol);

        if ($trades = Redis::get('trades.'.$symbol.$limit)) {
            $data = json_decode($trades);
            // $data->timestamp = time();

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        $pair = CoinPair::whereRaw("CONCAT(coin, market_coin) = '".$symbol."'")->first();

        if (!$pair) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Symbol.'
            ]);
        }

        if ($limit > 1000 || $limit < 1) {
            return response()->json([
                'success' => false,
                'message' => "Max limit is 1000"
            ]);
        }

        $txs = Transaction::where([['src_coin', $pair->coin], ['dest_coin', $pair->market_coin], ['type', TransactionType::EXCHANGE], ['main_exchange', 1]])
                    ->orWhere([['src_coin', $pair->market_coin], ['dest_coin', $pair->coin], ['type', TransactionType::EXCHANGE], ['main_exchange', 1]])
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->selectRaw("datetime,exchange_type,rate,dest_amount,src_amount,fee")
                    ->get();

        $data = array();
        foreach ($txs as $tx) {
            if ($tx->exchange_type == MarketOrderType::BUY) {
                array_push($data,
                    array(
                        'price' => $tx->rate,
                        'volume' => $tx->dest_amount,
                        'funds' => $tx->rate * $tx->dest_amount,
                        'side' => 'bid',
                        'timestamp' => strtotime($tx->datetime)
                    ));
            } else {
                array_push($data,
                    array(
                        'price' => $tx->rate,
                        'volume' => $tx->src_amount,
                        'funds' => $tx->rate * $tx->src_amount,
                        'side' => 'ask',
                        'timestamp' => strtotime($tx->datetime)
                    ));
            }
        }

        Redis::setex('trades.'.$symbol.$limit, 60 * 0.5, json_encode($data));

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
