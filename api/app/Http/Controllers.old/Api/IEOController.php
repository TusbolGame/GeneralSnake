<?php

namespace App\Http\Controllers\Api;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\IEOBonus;
use App\Entities\IEOCoin;
use App\Entities\IEOCoinLink;
use App\Entities\IEOCoinPair;
use App\Entities\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class IEOController extends Controller {
    public function getIEOCoins(Request $request) {
        $coins = IEOCoin::all();

        return response()->json([
            'success' => true,
            'data' => $coins
        ]);
    }

    public function getIEOCoinPairs(Request $request) {
        $pairs = IEOCoinPair::where('suspended', 0)
            ->leftJoin('ieo_coins', 'ieo_coins.coin', 'ieo_coin_pairs.coin')
            ->select('ieo_coins.*', 'ieo_coin_pairs.market_coin')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $pairs
        ]);
    }

    public function getIEOCoinLinks(Request $request) {
        $coin = $request->input('coin');

        $links = IEOCoinLink::where('coin', $coin)->get();

        return response()->json([
            'success' => true,
            'data' => $links
        ]);
    }

    public function getIEOBonus(Request $request) {
        $coin = $request->input('coin');

        $bonus = IEOBonus::where('coin', $coin)->get();

        return response()->json([
            'success' => true,
            'data' => $bonus
        ]);
    }

    public function getIEOStatistics(Request $request) {
        $icoType = TransactionType::ICO;
        $coin = $request->input('coin');
        if (!IEOCoin::where('coin', $coin)->first()) {
            return response()->json([
                'success' => false,
                'error' => 'Bad Coin'
            ]);
        }

        $query =    "SELECT SUM(dest_amount) ieo_amount, SUM(IF(src_coin = 'BTC', src_amount, 0)) btc_amount,  ".
                        "SUM(IF(src_coin = 'ETH', src_amount, 0)) eth_amount, SUM(IF(src_coin = 'TL', src_amount, 0)) tl_amount, SUM(IF(src_coin = 'USD', src_amount, 0)) usd_amount ".
                    "FROM transactions WHERE type = {$icoType} AND dest_coin = '{$coin}'";

        $result = \DB::select($query);

        $data = array();
        foreach ($result as $record) {
            if ($coin == 'W12') {
                $data['ieo_amount'] = $record->ieo_amount - 671557;
            } else {
                $data['ieo_amount'] = $record->ieo_amount ?: 0;
            }
            $data['btc_amount'] = $record->btc_amount ?: 0;
            $data['eth_amount'] = $record->eth_amount ?: 0;
            $data['tl_amount'] = $record->tl_amount ?: 0;
            $data['usd_amount'] = $record->usd_amount ?: 0;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getIEOHistory(Request $request) {
        $coin = $request->input('coin');
        $market_coin = $request->input('market_coin');
        $user = Auth::user();

        $result = Transaction::where('dest_coin', $coin)
                    ->where('src_coin', $market_coin)
                    ->where('type', TransactionType::ICO)
                    ->where('user_id', $user->id)
                    ->orderBy('datetime', 'desc')
                    ->get();

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function calcBonus($token, $usd_amount, $ieo_amount) {
        $bonus_amount = 0;
        switch ($token) {
            case 'LAAR':
                if ($usd_amount < 20)
                    $bonus_amount = $ieo_amount * 5 / 100;
                else if ($usd_amount >= 20 && $usd_amount < 50)
                    $bonus_amount = $ieo_amount * 10 / 100;
                else if ($usd_amount >= 50 && $usd_amount < 100)
                    $bonus_amount = $ieo_amount * 15 / 100;
                else if ($usd_amount >= 100)
                    $bonus_amount = $ieo_amount * 20 / 100;
                break;
            case 'MPAY':
                $bonus_amount = $ieo_amount * 20 / 100;
                break;
            case 'TGN':
                if ($usd_amount >= 0) {
                    $bonus_amount = $ieo_amount * 10 / 100;
                }
                break;
            case 'OXY2':
                if ($usd_amount >= 0) {
                    $bonus_amount = $ieo_amount * 20 / 100;
                }
                break;
            case 'W12':
                if ($usd_amount >= 0) {
                    $bonus_amount = $ieo_amount * 14.28 / 100;
                }
                break;
            case 'XLMG':
                if ($usd_amount >= 50 && $usd_amount < 150) {
                    $bonus_amount = $ieo_amount * 5 / 100;
                } else if ($usd_amount >= 150 && $usd_amount < 250) {
                    $bonus_amount = $ieo_amount * 10 / 100;
                } else if ($usd_amount >= 250) {
                    $bonus_amount = $ieo_amount * 15 / 100;
                }
                break;
            case 'SRX':
                if ($usd_amount >= 0 && $usd_amount <= 30) {
                    $bonus_amount = $ieo_amount * 5 / 100;
                } else if ($usd_amount > 30 && $usd_amount <= 50) {
                    $bonus_amount = $ieo_amount * 10 / 100;
                } else if ($usd_amount > 50 && $usd_amount <= 100) {
                    $bonus_amount = $ieo_amount * 15 / 100;
                } else if ($usd_amount > 100) {
                    $bonus_amount = $ieo_amount * 30 / 100;
                }
                break;
            default: break;
        }

        return $bonus_amount;
    }

    public function buyICO($user_id, $ico_coin, $buy_coin, $rate, $ico_amount, $buy_amount) {
        $tran = new Transaction();
        $tran->user_id = $user_id;
        $tran->datetime = Carbon::now();
        $tran->src_coin = $buy_coin;
        $tran->src_amount = round($buy_amount, 8);
        $tran->dest_coin = $ico_coin;
        $tran->dest_amount = round($ico_amount, 8);
        $tran->rate = $rate;
        $tran->fee = 0;
        $tran->type = TransactionType::ICO;
        $tran->status = TransactionStatus::SUCCESS;
        $tran->save();
    }

    public function buyToken(Request $request) {
        $input = $request->input();

        $user = Auth::user();

        if (!isset($input['amount']) || $input['amount'] <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please input amount correctly...'
            ]);
        }

        $coin = $input['coin'];
        $market_coin =  $input['market_coin'];

        $pair = IEOCoinPair::where([['suspended', 0], ['coin', $coin], ['market_coin', $market_coin]])->first();
        if (!$pair) {
            return response()->json([
                'success' => false,
                'error' => 'Incorrect coin'
            ]);
        }

        $amount = $input['amount'];

        $setting = IEOCoin::where('coin', $coin)->first();

        $usd_price = $setting['price'];
        $price = 0;

        switch ($market_coin) {
            case 'BTC':
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'USDT'], ['dest_coin', 'BTC']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $price = $usd_price / $sql->rate;
                } else {
                    $price = 0;
                }
                break;
            case 'ETH':
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'USDT'], ['dest_coin', 'ETH']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $price = $usd_price / $sql->rate;
                } else {
                    $price = 0;
                }
                break;
            case 'TL':
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'TL'], ['dest_coin', 'USDT']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $price = $usd_price * $sql->rate;
                } else {
                    $price = 0;
                }
                break;
            case 'USD':
                $price = $usd_price;
                break;
        }
        if ($price <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Bad Coin'
            ]);
        }

        $coin_amount = round($amount * $price, 8);

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $market_coin);
        if ($coin_amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your '.$market_coin.' balance is not enough to buy.'
            ]);
        }

        $ieo_amount = $amount;

        $usd_amount = $ieo_amount * $setting['usd_price'];

        $bonus_amount = $this->calcBonus($coin, $usd_amount, $ieo_amount);
        $ieo_amount += $bonus_amount;

        $this->buyICO($user->id, $coin, $market_coin, $price, $ieo_amount, $coin_amount);

        return response()->json([
            'success' => true
        ]);
    }
}
