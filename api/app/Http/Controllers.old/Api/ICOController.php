<?php

namespace App\Http\Controllers\Api;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\EpayPending;
use App\Entities\ICOFTCSetting;
use App\Entities\ICOLAARSetting;
use App\Entities\ICOMPAYSetting;
use App\Entities\ICOSTKSetting;
use App\Entities\IEOCoin;
use App\Entities\IEOCoinPair;
use App\Entities\Transaction;
use App\Libs\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ICOController extends Controller
{
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

    public function getFTCSetting(Request $request) {
        $setting = ICOFTCSetting::all()->pluck('value', 'name');

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    public function getFTCStatistics(Request $request) {
        $icoType = TransactionType::ICO;

        $query =    "SELECT SUM(dest_amount) ico_amount, SUM(IF(src_coin = 'BTC', src_amount, 0)) btc_amount,  ".
                        "SUM(IF(src_coin = 'ETH', src_amount, 0)) eth_amount, SUM(IF(src_coin = 'XRP', src_amount, 0)) xrp_amount ".
                    "FROM transactions WHERE type = {$icoType}";

        $result = \DB::select($query);

        $data = array();
        foreach ($result as $record) {
            $data['ico_amount'] = $record->ico_amount;
            $data['btc_amount'] = $record->btc_amount;
            $data['eth_amount'] = $record->eth_amount;
            $data['xrp_amount'] = $record->xrp_amount;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function buyFTC(Request $request) {
        $input = $request->input();

        $user = Auth::user();

        if (!isset($input['amount']) || $input['amount'] < 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please input amount correctly...'
            ]);
        }

        $buy_coin = $input['coin'];
        $coin_amount = $input['amount'];

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $buy_coin);

        if ($coin_amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your '.$buy_coin.' balance is not enough to buy.'
            ]);
        }

        $setting = ICOFTCSetting::all()->pluck('value', 'name');
        $curPhase = $setting['current_phase'];

        $price = 0;
        if ($curPhase == 1) {
            switch ($buy_coin) {
                case 'BTC':
                    $price = $setting['phase1_btc']; break;
                case 'ETH':
                    $price = $setting['phase1_eth']; break;
                case 'XRP':
                    $price = $setting['phase1_xrp']; break;
            }
        } else {
            switch ($buy_coin) {
                case 'BTC':
                    $price = $setting['phase2_btc']; break;
                case 'ETH':
                    $price = $setting['phase2_eth']; break;
                case 'XRP':
                    $price = $setting['phase2_xrp']; break;
            }
        }
        if ($price == 0) {
            return response()->json([
                'success' => false,
                'error' => 'Bad Coin'
            ]);
        }

        $this->buyICO($user->id, 'FTC', $buy_coin, $price, $coin_amount/$price, $coin_amount);

        return response()->json([
            'success' => true
        ]);
    }

    public function getSTKSetting(Request $request) {
        $setting = ICOSTKSetting::all()->pluck('value', 'name');

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    public function getSTKStatistics(Request $request) {
        $icoType = TransactionType::ICO;

        $query =    "SELECT SUM(dest_amount) ico_amount, SUM(IF(src_coin = 'BTC', src_amount, 0)) btc_amount,  ".
                        "SUM(IF(src_coin = 'ETH', src_amount, 0)) eth_amount, SUM(IF(src_coin = 'TL', src_amount, 0)) tl_amount ".
                    "FROM transactions WHERE type = {$icoType} AND dest_coin = 'STK'";

        $result = \DB::select($query);

        $data = array();
        foreach ($result as $record) {
            $data['ico_amount'] = $record->ico_amount ?: 0;
            $data['btc_amount'] = $record->btc_amount ?: 0;
            $data['eth_amount'] = $record->eth_amount ?: 0;
            $data['tl_amount'] = $record->tl_amount ?: 0;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function buySTK(Request $request) {
        $input = $request->input();

        $user = Auth::user();

        if (!isset($input['amount']) || $input['amount'] < 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please input amount correctly...'
            ]);
        }

        $buy_coin = $input['coin'];
        $coin_amount = $input['amount'];

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $buy_coin);

        if ($coin_amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your '.$buy_coin.' balance is not enough to buy.'
            ]);
        }

        $setting = ICOSTKSetting::all()->pluck('value', 'name');
        $curPhase = $setting['current_phase'];

        $minAmount = 0;

        switch ($buy_coin) {
            case 'BTC':
                $minAmount = $setting['btc_min']; break;
            case 'ETH':
                $minAmount = $setting['eth_min']; break;
            case 'TL':
                $minAmount = $setting['tl_min']; break;
        }

        if ($coin_amount < $minAmount) {
            return response()->json([
                'success' => false,
                'error' => 'Low Amount'
            ]);
        }

        $btc_price = 0;
        switch ($curPhase) {
            case 1:
                $btc_price = $setting['phase1_btc']; break;
            case 2:
                $btc_price = $setting['phase2_btc']; break;
            case 3:
                $btc_price = $setting['phase3_btc']; break;
        }
        $price = 0;
        switch ($buy_coin) {
            case 'BTC':
                $price = $btc_price; break;
            case 'ETH':
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'BTC'], ['dest_coin', 'ETH']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $price = $btc_price / $sql->rate;
                } else {
                    $price = 0;
                }
                break;
            case 'TL':
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'TL'], ['dest_coin', 'BTC']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $price = $btc_price * $sql->rate;
                } else {
                    $price = 0;
                }
                break;
        }
        if ($price == 0) {
            return response()->json([
                'success' => false,
                'error' => 'Bad Coin'
            ]);
        }

        $ico_amount = $coin_amount / $price;

        switch ($buy_coin) {
            case 'BTC':
                if ($coin_amount > 2)
                    $ico_amount += $ico_amount * 10 / 100;
                else if ($coin_amount > 1)
                    $ico_amount += $ico_amount * 8 / 100;
                else if ($coin_amount > 0.5)
                    $ico_amount += $ico_amount * 6 / 100;
                else if ($coin_amount > 0.1)
                    $ico_amount += $ico_amount * 4 / 100;
                break;
            case 'ETH':
                if ($coin_amount > 70)
                    $ico_amount += $ico_amount * 10 / 100;
                else if ($coin_amount > 35)
                    $ico_amount += $ico_amount * 8 / 100;
                else if ($coin_amount > 17.5)
                    $ico_amount += $ico_amount * 6 / 100;
                else if ($coin_amount > 3.5)
                    $ico_amount += $ico_amount * 4 / 100;
                break;
            case 'TL':
                if ($coin_amount > 40000)
                    $ico_amount += $ico_amount * 10 / 100;
                else if ($coin_amount > 20000)
                    $ico_amount += $ico_amount * 8 / 100;
                else if ($coin_amount > 10000)
                    $ico_amount += $ico_amount * 6 / 100;
                else if ($coin_amount > 2000)
                    $ico_amount += $ico_amount * 4 / 100;
                break;
        }
        $this->buyICO($user->id, 'STK', $buy_coin, $price, $ico_amount, $coin_amount);

        return response()->json([
            'success' => true
        ]);
    }

    public function getMPAYSetting(Request $request) {
        $setting = ICOMPAYSetting::all()->pluck('value', 'name');

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    public function getMPAYStatistics(Request $request) {
        $icoType = TransactionType::ICO;

        $query =    "SELECT SUM(dest_amount) ico_amount, SUM(IF(src_coin = 'BTC', src_amount, 0)) btc_amount,  ".
                        "SUM(IF(src_coin = 'ETH', src_amount, 0)) eth_amount, SUM(IF(src_coin = 'TL', src_amount, 0)) tl_amount ".
                    "FROM transactions WHERE type = {$icoType} AND dest_coin = 'MPAY'";

        $result = \DB::select($query);

        $data = array();
        foreach ($result as $record) {
            $data['ico_amount'] = $record->ico_amount ?: 0;
            $data['btc_amount'] = $record->btc_amount ?: 0;
            $data['eth_amount'] = $record->eth_amount ?: 0;
            $data['tl_amount'] = $record->tl_amount ?: 0;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function buyMPAY(Request $request) {
        $input = $request->input();

        $user = Auth::user();

        if (!isset($input['amount']) || $input['amount'] <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please input amount correctly...'
            ]);
        }

        $buy_coin = $input['coin'];
        $mpay_amount = $input['amount'];

        $setting = ICOMPAYSetting::all()->pluck('value', 'name');

        $minAmount = $setting['min_amount'];;

        if ($mpay_amount < $minAmount) {
            return response()->json([
                'success' => false,
                'error' => 'Low Amount'
            ]);
        }

        $usd_price = $setting['usd_price'];
        $price = 0;

        switch ($buy_coin) {
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
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'USDT'], ['dest_coin', 'BTC']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $btc_price = $sql->rate;
                } else {
                    $btc_price = 0;
                }
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'TL'], ['dest_coin', 'BTC']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $price = $usd_price / $btc_price * $sql->rate;
                } else {
                    $price = 0;
                }
                break;
        }
        if ($price <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Bad Coin'
            ]);
        }

        $coin_amount = round($mpay_amount * $price, 8);

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $buy_coin);
        if ($coin_amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your '.$buy_coin.' balance is not enough to buy.'
            ]);
        }

        $ico_amount = $mpay_amount;

        $ico_amount += $ico_amount * 20 / 100;
//        switch ($buy_coin) {
//            case 'BTC':
//                if ($coin_amount > 2)
//                    $ico_amount += $ico_amount * 10 / 100;
//                else if ($coin_amount > 1)
//                    $ico_amount += $ico_amount * 8 / 100;
//                else if ($coin_amount > 0.5)
//                    $ico_amount += $ico_amount * 6 / 100;
//                else if ($coin_amount > 0.1)
//                    $ico_amount += $ico_amount * 4 / 100;
//                break;
//            case 'ETH':
//                if ($coin_amount > 70)
//                    $ico_amount += $ico_amount * 10 / 100;
//                else if ($coin_amount > 35)
//                    $ico_amount += $ico_amount * 8 / 100;
//                else if ($coin_amount > 17.5)
//                    $ico_amount += $ico_amount * 6 / 100;
//                else if ($coin_amount > 3.5)
//                    $ico_amount += $ico_amount * 4 / 100;
//                break;
//            case 'XRP':
//                if ($coin_amount > 40000)
//                    $ico_amount += $ico_amount * 10 / 100;
//                else if ($coin_amount > 20000)
//                    $ico_amount += $ico_amount * 8 / 100;
//                else if ($coin_amount > 10000)
//                    $ico_amount += $ico_amount * 6 / 100;
//                else if ($coin_amount > 2000)
//                    $ico_amount += $ico_amount * 4 / 100;
//                break;
//        }
        $this->buyICO($user->id, 'MPAY', $buy_coin, $price, $ico_amount, $coin_amount);

        return response()->json([
            'success' => true
        ]);
    }

    public function getLAARSetting(Request $request) {
        $setting = ICOLAARSetting::all()->pluck('value', 'name');

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    public function getLAARStatistics(Request $request) {
        $icoType = TransactionType::ICO;

        $query =    "SELECT SUM(dest_amount) ico_amount, SUM(IF(src_coin = 'BTC', src_amount, 0)) btc_amount,  ".
                        "SUM(IF(src_coin = 'ETH', src_amount, 0)) eth_amount, SUM(IF(src_coin = 'TL', src_amount, 0)) tl_amount, SUM(IF(src_coin = 'USD', src_amount, 0)) usd_amount ".
                    "FROM transactions WHERE type = {$icoType} AND dest_coin = 'LAAR'";

        $result = \DB::select($query);

        $data = array();
        foreach ($result as $record) {
            $data['ico_amount'] = $record->ico_amount ?: 0;
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

    public function buyLAAR(Request $request) {
        $input = $request->input();

        $user = Auth::user();

        if (!isset($input['amount']) || $input['amount'] <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please input amount correctly...'
            ]);
        }

        $buy_coin = $input['coin'];
        $LAAR_amount = $input['amount'];

        $setting = ICOLAARSetting::all()->pluck('value', 'name');

        $minAmount = $setting['min_amount'];;

        if ($LAAR_amount < $minAmount) {
            return response()->json([
                'success' => false,
                'error' => 'Low Amount'
            ]);
        }

        $usd_price = $setting['usd_price'];
        $price = 0;

        switch ($buy_coin) {
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
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'USDT'], ['dest_coin', 'BTC']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $btc_price = $sql->rate;
                } else {
                    $btc_price = 0;
                }
                $sql = Transaction::where([['type', TransactionType::EXCHANGE], ['src_coin', 'TL'], ['dest_coin', 'BTC']])
                    ->orderBy('id', 'desc')->first();
                if ($sql) {
                    $price = $usd_price / $btc_price * $sql->rate;
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

        $coin_amount = round($LAAR_amount * $price, 8);

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $buy_coin);
        if ($coin_amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your '.$buy_coin.' balance is not enough to buy.'
            ]);
        }

        $ico_amount = $LAAR_amount;

        $usd_amount = $ico_amount * $setting['usd_price'];

        if ($usd_amount < 20)
            $ico_amount += $ico_amount * 5 / 100;
        else if ($usd_amount >= 20 && $usd_amount < 50)
            $ico_amount += $ico_amount * 10 / 100;
        else if ($usd_amount >= 50 && $usd_amount < 100)
            $ico_amount += $ico_amount * 15 / 100;
        else if ($usd_amount >= 100)
            $ico_amount += $ico_amount * 20 / 100;

        $this->buyICO($user->id, 'LAAR', $buy_coin, $price, $ico_amount, $coin_amount);

        return response()->json([
            'success' => true
        ]);
    }

    public function getIEOCoins(Request $request) {
        $coins = IEOCoin::all();

        return response()->json([
            'success' => true,
            'data' => $coins
        ]);
    }

    public function getIEOCoinPairs(Request $request) {
        $pairs = IEOCoinPair::all();

        return response()->json([
            'success' => true,
            'data' => $pairs
        ]);
    }
}
