<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Entities\Coin;
use App\Entities\CoinPair;
use App\Entities\Order;
use App\Entities\Setting;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\Transaction;
use App\Entities\UsersFee;
use App\Events\OrderEvent;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function addBuyExchange($user_id, $coin, $marketCoin, $amount, $rate, $feeRate, $refRate, $places, $main = false) {
        $amount = round($amount, 8);
        if ($amount <= 0) {
            return;
        }

        $feeAmount = round($amount * $feeRate / 100, 8);

        $tran = new Transaction();
        $tran->user_id = $user_id;
        $tran->datetime = Carbon::now();
        $tran->src_coin = $marketCoin;
        $tran->src_amount = round($rate * $amount, $places);
        $tran->dest_coin = $coin;
        $tran->dest_amount = round($amount - $feeAmount, 8);
        $tran->rate = $rate;
        $tran->fee = $feeAmount;
        $tran->fee_rate = $feeRate;
        $tran->type = TransactionType::EXCHANGE;
        $tran->exchange_type = MarketOrderType::BUY;
        $tran->status = TransactionStatus::SUCCESS;
        if ($main) {
            $tran->main_exchange = 1;
        }
        $tran->save();

        $user = User::find($user_id);
        if ($user && $user->ref_id && $user->ref_id > 0) {
            $bonusAmount = round($feeAmount * $refRate / 100, 8);

            if ($bonusAmount > 0) {
                $tran = new Transaction();
                $tran->user_id = $user->ref_id;
                $tran->datetime = Carbon::now();
                $tran->dest_coin = $coin;
                $tran->dest_amount = $bonusAmount;
                $tran->rate = $rate;
                $tran->fee = (-1) * $bonusAmount;
                $tran->fee_rate = $feeRate;
                $tran->type = TransactionType::BONUS;
                $tran->status = TransactionStatus::SUCCESS;
                $tran->ref_id = $user_id;
                $tran->save();
            }
        }
    }

    public function addSellExchange($user_id, $coin, $marketCoin, $amount, $rate, $feeRate, $refRate, $places, $main = false) {
        $amount = round($amount, 8);
        if ($amount <= 0) {
            return;
        }

        $feeAmount = round($rate * $amount * $feeRate / 100, $places);
        $dest_amount = round($rate* $amount - $feeAmount, $places);

        $tran = new Transaction();

        $tran->user_id = $user_id;
        $tran->datetime = Carbon::now();
        $tran->src_coin = $coin;
        $tran->src_amount = $amount;
        $tran->dest_coin = $marketCoin;
        $tran->dest_amount = $dest_amount;
        $tran->rate = $rate;
        $tran->fee = $feeAmount;
        $tran->fee_rate = $feeRate;
        $tran->type = TransactionType::EXCHANGE;
        $tran->exchange_type = MarketOrderType::SELL;
        $tran->status = TransactionStatus::SUCCESS;
        if ($main) {
            $tran->main_exchange = 1;
        }
        $tran->save();

        $user = User::find($user_id);
        if ($user && $user->ref_id && $user->ref_id > 0) {
            $bonusAmount = round($feeAmount * $refRate / 100, $places);

            if ($bonusAmount > 0) {
                $tran = new Transaction();
                $tran->user_id = $user->ref_id;
                $tran->datetime = Carbon::now();
                $tran->dest_coin = $marketCoin;
                $tran->dest_amount = $bonusAmount;
                $tran->rate = $rate;
                $tran->fee = (-1) * $bonusAmount;
                $tran->fee_rate = $feeRate;
                $tran->type = TransactionType::BONUS;
                $tran->status = TransactionStatus::SUCCESS;
                $tran->ref_id = $user_id;
                $tran->save();
            }
        }
    }

    public function buyOrder($coin, $marketCoin, Request $request) {
        $input = $request->input();

        if (!isset($input['amount'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount to buy'
            ]);
        }

        if (!isset($input['rate'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter exchange rate to buy'
            ]);
        }

        $amount = $input['amount'];
        $rate = $input['rate'];

        $pairInfo = CoinPair::where([['coin', $coin], ['market_coin', $marketCoin], ['suspended', 0]])->first();
        if (!$pairInfo) {
            return response()->json([
                'success' => false,
                'error' => 'Please select coin.'
            ]);
        }

        $rate = round($rate, $pairInfo->places);
        $amount = round($amount, 8);

        if ($amount < env('ORDER_MINIMUM_AMOUNT')) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount greater than 0.001'
            ]);
        }

        if ($amount < 250 && $coin == 'ETN') {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount greater than 250 ETN'
            ]);
        }

        if ($rate <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter price greater than 0'
            ]);
        }
        $coinInfo = Coin::where('coin', $coin)->first();

        $user = Auth::user();

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $marketCoin);

        if ($rate * $amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your '.$marketCoin.' balance is not enough to buy.'
            ]);
        }

        $buyAmount = 0;
        $users = array();
        $rates = array();
        $trades = array();
        $last_price = '';
        $volume = 0;

        $sellOrders = Order::where([['src_coin', $coin], ['dest_coin', $marketCoin], ['rate', '<=', $rate]])
                        ->orderBy('rate')
                        ->orderBy('created_at')
                        ->get();

        $buy_fee = $coinInfo->buy_fee;
        $userFee = UsersFee::where([['user_id', $user->id], ['coin', $coin], ['market_coin', $marketCoin]])->first();

        if ($userFee && $userFee->buy_fee && !is_null($userFee->buy_fee)) {
            $buy_fee = $userFee->buy_fee;
        }

        foreach ($sellOrders as $order) {
            if ($order->src_amount > $amount) {
                $buyAmount += $amount;

                $sell_fee = $coinInfo->sell_fee;
                $userFee = UsersFee::where([['user_id', $order->user_id], ['coin', $coin], ['market_coin', $marketCoin]])->first();

                if ($userFee && $userFee->sell_fee && !is_null($userFee->sell_fee)) {
                    $sell_fee = $userFee->sell_fee;
                }
                $this->addSellExchange($order->user_id, $coin, $marketCoin, $amount, $order->rate, $sell_fee, $coinInfo->ref_fee, $pairInfo->places);

                $this->addBuyExchange($user->id, $coin, $marketCoin, $amount, $order->rate, $buy_fee, $coinInfo->ref_fee, $pairInfo->places, true);

                $last_price = $order->rate;

                if (!in_array($order->user_id, $users)) {
                    array_push($users, $order->user_id);
                }

                if (!in_array($order->rate, $rates)) {
                    array_push($rates, $order->rate);
                }

                array_push($trades, array(
                    'rate' => $order->rate,
                    'amount' => $amount,
                    'type' => MarketOrderType::BUY
                ));

                if (round($order->src_amount - $amount, 8) <= 0) {
                    $order->delete();
                } else {
                    $order->src_amount = round($order->src_amount - $amount, 8);
                    $order->dest_amount = round($order->rate * $order->src_amount, 8);
                    $order->save();
                }

                $amount = 0;
                break;
            } else {
                $buyAmount += $order->src_amount;

                $sell_fee = $coinInfo->sell_fee;
                $userFee = UsersFee::where([['user_id', $order->user_id], ['coin', $coin], ['market_coin', $marketCoin]])->first();

                if ($userFee && $userFee->sell_fee && !is_null($userFee->sell_fee)) {
                    $sell_fee = $userFee->sell_fee;
                }
                $this->addSellExchange($order->user_id, $coin, $marketCoin, $order->src_amount, $order->rate, $sell_fee, $coinInfo->ref_fee, $pairInfo->places);

                $this->addBuyExchange($user->id, $coin, $marketCoin, $order->src_amount, $order->rate, $buy_fee, $coinInfo->ref_fee, $pairInfo->places, true);
                $last_price = $order->rate;

                $amount = $amount - $order->src_amount;
                if (!in_array($order->user_id, $users)) {
                    array_push($users, $order->user_id);
                }

                if (!in_array($order->rate, $rates)) {
                    array_push($rates, $order->rate);
                }

                array_push($trades, array(
                    'rate' => $order->rate,
                    'amount' => $order->src_amount,
                    'type' => MarketOrderType::BUY
                ));
                $order->delete();
            }
            if ($amount <= 0) break;
        }

        if ($buyAmount > 0) {
            $volume = $buyAmount;
            if (!in_array($user->id, $users)) {
                array_push($users, $user->id);
            }
        }

        if ($amount > 0) {
            $buyOrder = new Order();

            $buyOrder->type = MarketOrderType::BUY;
            $buyOrder->src_coin = $marketCoin;
            $buyOrder->dest_coin = $coin;
            $buyOrder->src_amount = round($amount * $rate, 8);
            $buyOrder->dest_amount = $amount;
            $buyOrder->user_id = $user->id;
            $buyOrder->rate = $rate;

            if (!in_array($user->id, $users)) {
                array_push($users, $user->id);
            }

            if (!in_array($rate, $rates)) {
                array_push($rates, $rate);
            }
            $buyOrder->save();
        }

        $buySellAmount = array();
        foreach ($rates as $rate) {
            array_push($buySellAmount, array(
                'rate' => round($rate, 8),
                'buyAmount' => 0,
                'sellAmount' => 0
            ));
        }

        $buyAmount = Order::where([['dest_coin', $coin], ['src_coin', $marketCoin]])->whereIn('rate', $rates)->groupBy('rate')->select('rate', \DB::raw('SUM(dest_amount) AS amount'))->get();
        foreach ($buyAmount as $record) {
            foreach ($buySellAmount as $key=>$amount) {
                if ($amount['rate'] == round($record->rate, 8)) {
                    $buySellAmount[$key]['buyAmount'] = $record->amount;
                    break;
                }
            }
        }
        $sellAmount = Order::where([['src_coin', $coin], ['dest_coin', $marketCoin]])->whereIn('rate', $rates)->groupBy('rate')->select('rate', \DB::raw('SUM(src_amount) AS amount'))->get();
        foreach ($sellAmount as $record) {
            foreach ($buySellAmount as $key=>$amount) {
                if ($amount['rate'] == round($record->rate, 8)) {
                    $buySellAmount[$key]['sellAmount'] = $record->amount;
                    break;
                }
            }
        }

        event(new OrderEvent(array(
            'users' => $users,
            'rates' => $buySellAmount,
            'last_price' => $last_price,
            'volume' => $volume,
            'coin' => $coin,
            'marketCoin' => $marketCoin,
            'trades' => $trades,
            'datetime' => Carbon::now()->toDateTimeString()
        )));

        return response()->json([
            'success' => true,
        ]);
    }

    public function sellOrder($coin, $marketCoin, Request $request) {
        $input = $request->input();

        if (!isset($input['amount'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount to sell'
            ]);
        }

        if (!isset($input['rate'])) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter exchange rate to sell'
            ]);
        }


        $amount = $input['amount'];
        $rate = $input['rate'];

        $pairInfo = CoinPair::where([['coin', $coin], ['market_coin', $marketCoin], ['suspended', 0]])->first();
        if (!$pairInfo) {
            return response()->json([
                'success' => false,
                'error' => 'Please select coin.'
            ]);
        }

        $rate = round($rate, $pairInfo->places);
        $amount = round($amount, 8);

        if ($amount < env('ORDER_MINIMUM_AMOUNT')) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount greater than 0.001'
            ]);
        }

        if ($amount < 250 && $coin == 'ETN') {
            return response()->json([
                'success' => false,
                'error' => 'Please enter amount greater than 250 ETN'
            ]);
        }

        if ($rate <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter price greater than 0'
            ]);
        }
        $coinInfo = Coin::where('coin', $coin)->first();

        $user = Auth::user();

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $coin);
        
        if ($amount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your '.$coin.' balance is not enough to sell.'
            ]);
        }

        $sellAmount = 0;
        $users = array();
        $rates = array();
        $trades = array();
        $last_price = '';
        $volume = 0;

        $buyOrders = Order::where([['src_coin', $marketCoin], ['dest_coin', $coin], ['rate', '>=', $rate]])
                        ->orderBy('rate', 'desc')
                        ->orderBy('created_at')
                        ->get();

        $sell_fee = $coinInfo->sell_fee;
        $userFee = UsersFee::where([['user_id', $user->id], ['coin', $coin], ['market_coin', $marketCoin]])->first();

        if ($userFee && $userFee->sell_fee && !is_null($userFee->sell_fee)) {
            $sell_fee = $userFee->sell_fee;
        }

        foreach ($buyOrders as $order) {
            if ($order->dest_amount > $amount) {
                $sellAmount += $amount;

                $buy_fee = $coinInfo->buy_fee;
                $userFee = UsersFee::where([['user_id', $order->user_id], ['coin', $coin], ['market_coin', $marketCoin]])->first();

                if ($userFee && $userFee->buy_fee && !is_null($userFee->buy_fee)) {
                    $buy_fee = $userFee->buy_fee;
                }
                $this->addBuyExchange($order->user_id, $coin, $marketCoin, $amount, $order->rate, $buy_fee, $coinInfo->ref_fee, $pairInfo->places);

                $this->addSellExchange($user->id, $coin, $marketCoin, $amount, $order->rate, $sell_fee, $coinInfo->ref_fee, $pairInfo->places, true);

                $last_price = $order->rate;

                if (!in_array($order->user_id, $users)) {
                    array_push($users, $order->user_id);
                }

                if (!in_array($order->rate, $rates)) {
                    array_push($rates, $order->rate);
                }

                array_push($trades, array(
                    'rate' => $order->rate,
                    'amount' => $amount,
                    'type' => MarketOrderType::SELL
                ));

                if (round($order->dest_amount - $amount, 8) <= 0) {
                    $order->delete();
                } else {
                    $order->dest_amount = round($order->dest_amount - $amount, 8);
                    $order->src_amount = round($order->rate * $order->dest_amount, 8);
                    $order->save();
                }

                $amount = 0;
                break;
            } else {
                $sellAmount += $order->dest_amount;

                $buy_fee = $coinInfo->buy_fee;
                $userFee = UsersFee::where([['user_id', $order->user_id], ['coin', $coin], ['market_coin', $marketCoin]])->first();

                if ($userFee && $userFee->buy_fee && !is_null($userFee->buy_fee)) {
                    $buy_fee = $userFee->buy_fee;
                }

                $this->addBuyExchange($order->user_id, $coin, $marketCoin, $order->dest_amount, $order->rate, $buy_fee, $coinInfo->ref_fee, $pairInfo->places);

                $this->addSellExchange($user->id, $coin, $marketCoin, $order->dest_amount, $order->rate, $sell_fee, $coinInfo->ref_fee, $pairInfo->places, true);

                $last_price = $order->rate;

                $amount = $amount - $order->dest_amount;
                if (!in_array($order->user_id, $users)) {
                    array_push($users, $order->user_id);
                }

                if (!in_array($order->rate, $rates)) {
                    array_push($rates, $order->rate);
                }

                array_push($trades, array(
                    'rate' => $order->rate,
                    'amount' => $order->dest_amount,
                    'type' => MarketOrderType::SELL
                ));
                $order->delete();
            }
            if ($amount <= 0) break;
        }

        if ($sellAmount > 0) {
            $volume = $sellAmount;
            if (!in_array($user->id, $users)) {
                array_push($users, $user->id);
            }
        }

        if ($amount > 0) {
            $sellOrder = new Order();

            $sellOrder->type = MarketOrderType::SELL;
            $sellOrder->src_coin = $coin;
            $sellOrder->dest_coin = $marketCoin;
            $sellOrder->src_amount = $amount;
            $sellOrder->dest_amount = round($amount * $rate, 8);
            $sellOrder->user_id = $user->id;
            $sellOrder->rate = $rate;
            $sellOrder->save();

            if (!in_array($user->id, $users)) {
                array_push($users, $user->id);
            }

            if (!in_array($rate, $rates)) {
                array_push($rates, $rate);
            }
        }

        $buySellAmount = array();
        foreach ($rates as $rate) {
            array_push($buySellAmount, array(
                'rate' => round($rate, 8),
                'buyAmount' => 0,
                'sellAmount' => 0
            ));
        }

        $buyAmount = Order::where([['dest_coin', $coin], ['src_coin', $marketCoin]])->whereIn('rate', $rates)->groupBy('rate')->select('rate', \DB::raw('SUM(dest_amount) AS amount'))->get();
        foreach ($buyAmount as $record) {
            foreach ($buySellAmount as $key=>$amount) {
                if ($amount['rate'] == round($record->rate, 8)) {
                    $buySellAmount[$key]['buyAmount'] = $record->amount;
                    break;
                }
            }
        }
        $sellAmount = Order::where([['src_coin', $coin], ['dest_coin', $marketCoin]])->whereIn('rate', $rates)->groupBy('rate')->select('rate', \DB::raw('SUM(src_amount) AS amount'))->get();
        foreach ($sellAmount as $record) {
            foreach ($buySellAmount as $key=>$amount) {
                if ($amount['rate'] == round($record->rate, 8)) {
                    $buySellAmount[$key]['sellAmount'] = $record->amount;
                    break;
                }
            }
        }

        event(new OrderEvent(array(
            'users' => $users,
            'rates' => $buySellAmount,
            'last_price' => $last_price,
            'volume' => $volume,
            'coin' => $coin,
            'marketCoin' => $marketCoin,
            'trades' => $trades,
            'datetime' => Carbon::now()->toDateTimeString()
        )));

        return response()->json([
            'success' => true,
        ]);
    }

    public function getBuyOrder($coin, $marketCoin, Request $request) {
        $orders = Order::where([['src_coin', $marketCoin], ['dest_coin', $coin]])
            ->groupBy('rate')
            ->orderBy('rate', 'DESC')
            ->select('rate', \DB::raw('SUM(dest_amount) AS amount'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getSellOrder($coin, $marketCoin, Request $request) {
        $orders = Order::where([['src_coin', $coin], ['dest_coin', $marketCoin]])
            ->groupBy('rate')
            ->orderBy('rate', 'ASC')
            ->select('rate', \DB::raw('SUM(src_amount) AS amount'))
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getLastOrders($coin, $marketCoin, Request $request) {
        $orders = Transaction::where([['src_coin', $coin], ['dest_coin', $marketCoin], ['type', TransactionType::EXCHANGE], ['main_exchange', 1]])
                    ->orWhere([['src_coin', $marketCoin], ['dest_coin', $coin], ['type', TransactionType::EXCHANGE], ['main_exchange', 1]])
                    ->orderBy('created_at', 'DESC')
                    ->limit(20)
                    ->selectRaw("id,datetime,exchange_type,rate,dest_amount,src_amount,fee")
                    ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function deleteOrder(Request $request) {
        $user = Auth::user();

        $id = $request->input('id');

        $order = Order::find($id);

        if(!$order) {
            return response()->json([
                'success' => false,
                'error' => 'Order does not exist'
            ]);
        }

        if ($user->role != 'admin' && $user->role != 'superadmin' && $order->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Order does not exist'
            ]);
        }

        $rate = $order->rate;

        if ($order->type == MarketOrderType::BUY) {
            $coin = $order->dest_coin;
            $marketCoin = $order->src_coin;
        } else {
            $coin = $order->src_coin;
            $marketCoin = $order->dest_coin;
        }

        $order->delete();

        $buyAmount = Order::where([['dest_coin', $coin], ['src_coin', $marketCoin], ['rate', $rate]])->sum('dest_amount');
        $sellAmount = Order::where([['src_coin', $coin], ['dest_coin', $marketCoin], ['rate', $rate]])->sum('src_amount');

        event(new OrderEvent(array(
            'users' => array(),
            'rates' => array(
                array(
                    'rate' => round($rate, 8),
                    'buyAmount' => $buyAmount,
                    'sellAmount' => $sellAmount
                )
            ),
            'coin' => $coin,
            'marketCoin' => $marketCoin,
            'datetime' => Carbon::now()->toDateTimeString()
        )));

        return response()->json([
            'success' => true,
        ]);
    }

    public function getPendingOrder($coin, $marketCoin, Request $request) {
        $user = Auth::user();

        $pair = $coin.'-'.$marketCoin;
        $orders = Order::where('user_id', $user->id)
            ->whereRaw("(CONCAT(src_coin, '-', dest_coin) = '".$pair."' OR CONCAT(dest_coin, '-', src_coin) = '".$pair."')")
            ->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getPastOrder($coin, $marketCoin, Request $request) {
        $user = Auth::user();

        $pair = $coin.'-'.$marketCoin;
        $orders = Transaction::where([['user_id', $user->id], ['type', TransactionType::EXCHANGE]])
            ->whereRaw("(CONCAT(src_coin, '-', dest_coin) = '".$pair."' OR CONCAT(dest_coin, '-', src_coin) = '".$pair."')")
            ->orderBy('created_at', 'desc')->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getUserAllPendingOrder(Request $request) {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getUserAllPastOrder(Request $request) {
        $user = Auth::user();

        $orders = Transaction::where([['user_id', $user->id], ['type', TransactionType::EXCHANGE]])->orderBy('created_at', 'desc')->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getStatistics($coin, $marketCoin, Request $request) {
        $pair = $coin.'-'.$marketCoin;

        $last_price = Transaction::where([['type', TransactionType::EXCHANGE], ['status', TransactionStatus::SUCCESS]])
            ->whereRaw("(CONCAT(src_coin, '-', dest_coin) = '".$pair."' OR CONCAT(dest_coin, '-', src_coin) = '".$pair."')")
            ->orderBy('created_at', 'desc')->first();

        $lastday = Carbon::now()->addDay(-1);

        $result = Transaction::where([['type', TransactionType::EXCHANGE], ['status', TransactionStatus::SUCCESS], ['exchange_type', MarketOrderType::SELL], ['datetime', '>=', $lastday]])
            ->select(\DB::raw("MAX(rate) AS max_price"), \DB::raw("MIN(rate) AS min_price"), \DB::raw("SUM(src_amount) / 2 AS volume"), \DB::raw("AVG(rate) AS avg_price"))->get();

        return response()->json([
            'success' => true,
            'data' => $result[0],
            'last_price' => $last_price ? $last_price['rate'] : 0
        ]);
    }

    public function getOpenAllOrders($coin, $marketCoin, Request $request) {
        $pair = $coin.'-'.$marketCoin;

        $orders = Order::whereRaw("(CONCAT(src_coin, '-', dest_coin) = '".$pair."' OR CONCAT(dest_coin, '-', src_coin) = '".$pair."')")
            ->leftJoin('users', 'orders.user_id', 'users.id')
            ->orderBy('orders.created_at', 'DESC')
            ->select(\DB::raw('orders.*'), 'users.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
}
