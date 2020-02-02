<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Constants\TransactionType;
use App\Entities\AdminSetting;
use App\Entities\BitcoenAddress;
use App\Entities\BitwhiteAddress;
use App\Entities\Coin;
use App\Entities\CoinAddress;
use App\Entities\CoinPair;
use App\Entities\IEOCoinPair;
use App\Entities\NextyAddress;
use App\Entities\ONTAddress;
use App\Entities\ONTSetting;
use App\Entities\PirlAddress;
use App\Entities\Profile;
use App\Constants\TransactionStatus;
use App\Entities\Transaction;
use App\Entities\TronAddress;
use App\Entities\XinAddress;
use App\Libs\BitcoenRPC;
use App\Libs\JsonRPCClient;
use App\Libs\Utility;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AdminController extends Controller
{
    //

    const PIRL_UNIT = 1 / 1000000000000000000;
    const NTY_UNIT = 1 / 1000000000000000000;
    const XIN_UNIT = 1 / 100000000;
    const USDT_PROPERTY = 31;
    const BTW_UNIT = 1 / 100000000;
    const TRX_UNIT = 1 / 1000000;
    const TRC20_DECIMALS = array(
        'IGG' => 6
    );
    const TRC10_DECIMALS = array(
        'BTT' => 6
    );

    const CLB_UNIT = 1 / 100000000;
    const WAVES_UNIT = 1 / 100000000;
    const ADMN_UNIT = 1 / 100000000;
    const CLB_ASSETID = 'CfMBo6GAZZhXs3bbS64NwAor24CuXwYVGJ34SSkPKHVf';
    const ADMN_ASSETID = '2ad6RZP9DVntNGNkS5so4oax2x2yZV4ZTr2yCNrpj1VN';

    const ONG_UNIT = 1 / 1000000000;
    const ONT_UNIT = 1;

    public function getUsers(Request $request) {
        $input = $request->input();

        $users = User::where('role', 'user')
                ->leftJoin('profiles', 'users.id', 'profiles.user_id')
                ->select('users.id', 'users.email', 'users.name', 'users.created_at', 'users.confirmed', 'profiles.verify_status', 'profiles.phone_number')->orderBy('users.created_at', 'asc');
        if (isset($input['verify_status'])) {
            $users->where('profiles.verify_status', $input['verify_status']);
        }

        $data = $users->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function updateUserProfile(Request $request) {
        $input = $request->input();

        $profile = Profile::find($input['id']);

        $user = User::find($input['user_id']);

        if (!$profile || !$user) {
            return response()->json([
                'success' => false,
                'error' => 'User Not Exist'
            ]);
        }

        if ($input['changed_email']) {
            $findUser = User::where('email', $input['email'])->first();

            if ($findUser) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email Exist'
                ]);
            }

            // $user->email = $input['email'];
        }

        $profile->firstname = $input['firstname'];
        $profile->surname = $input['surname'];
        $profile->phone_number = $input['phone_number'];
        $profile->address = $input['address'];
        $profile->attach_name = $input['attach_name'];
        $profile->verify_type = $input['verify_type'];
        $profile->idcard_url = $input['idcard_url'];
        $profile->idcard_number = $input['idcard_number'];
        $profile->verify_status = $input['verify_status'];
        $profile->iban_code = $input['iban_code'];
        $profile->allowed_g2f = $input['allowed_g2f'];
        $profile->save();

        $user->name = $input['firstname']." ".$input['surname'];
        $user->save();


        return response()->json([
            'success' => true
        ]);
    }

    public function getCoinUsers($coin, Request $request) {
        $input = $request->input();

        $tran_query =   " SELECT user_id, ".
                            " SUM(IF(dest_coin = '{$coin}' AND type = ".TransactionType::DEPOSIT." AND status = ".TransactionStatus::SUCCESS.", dest_amount, 0)) deposit_amount, ".
                            " SUM(IF(dest_coin = '{$coin}' AND (type = ".TransactionType::EXCHANGE." OR type = ".TransactionType::FASTBUY." OR type = ".TransactionType::BONUS." OR type = ".TransactionType::ICO."), dest_amount, 0)) buy_amount, ".
                            " SUM(IF(src_coin = '{$coin}' AND (type = ".TransactionType::EXCHANGE." OR type = ".TransactionType::FASTBUY." OR type = ".TransactionType::ICO."), src_amount, 0)) sell_amount, ".
                            " SUM(IF(src_coin = '{$coin}' AND type = ".TransactionType::WITHDRAW." AND status = ".TransactionStatus::SUCCESS.", src_amount, 0)) withdraw_amount, ".
                            " SUM(IF(src_coin = '{$coin}' AND type = ".TransactionType::WITHDRAW." AND status = ".TransactionStatus::SUCCESS." AND self_withdraw = 1, src_amount, 0)) self_withdraw_amount, ".
                            " SUM(IF(src_coin = '{$coin}' AND type = ".TransactionType::WITHDRAW." AND status = ".TransactionStatus::PENDING.", src_amount, 0)) pending_amount, ".
                            " SUM(IF(dest_coin = '{$coin}' AND type = ".TransactionType::RECORD." AND status = ".TransactionStatus::SUCCESS.", dest_amount, 0)) manual_amount ".
                        "FROM transactions ".
                        "GROUP BY user_id ".
                        "UNION ALL ".
                        "SELECT user_id, ".
                            " 0 deposit_amount, ".
                            " 0 buy_amount, ".
                            " SUM(IF(src_coin = '{$coin}' AND type = ".MarketOrderType::SELL.", src_amount, 0)) sell_amount, ".
                            " 0 withdraw_amount, ".
                            " 0 self_withdraw_amount, ".
                            " 0 pending_amount, ".
                            " 0 manual_amount ".
                        "FROM orders ".
                        "GROUP BY user_id ";

        $query =    "SELECT users.id, users.email,  ".
                        "SUM(IFNULL(trans.deposit_amount, 0)) deposit_amount, ".
                        "SUM(IFNULL(trans.buy_amount, 0)) buy_amount, ".
                        "SUM(IFNULL(trans.sell_amount, 0)) sell_amount, ".
                        "SUM(IFNULL(trans.withdraw_amount, 0)) withdraw_amount, ".
                        "SUM(IFNULL(trans.self_withdraw_amount, 0)) self_withdraw_amount, ".
                        "SUM(IFNULL(trans.pending_amount, 0)) pending_amount, ".
                        "SUM(IFNULL(trans.manual_amount, 0)) manual_amount ".
                    "FROM users LEFT JOIN (".$tran_query.") AS trans ON users.id = trans.user_id ".
                    "WHERE users.role = 'user' ".
                    "GROUP BY users.id, users.email ".
                    "ORDER BY users.created_at ASC ";

        $result = \DB::select($query);

        $data = array();
        foreach ($result as $user) {
            if (!isset($user->deposit_amount)) {
                $user->deposit_amount = 0;
            }
            $user->balance_amount = $user->deposit_amount + $user->buy_amount - $user->sell_amount - $user->withdraw_amount - $user->pending_amount + $user->manual_amount;
            array_push($data, $user);
        }
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getUserById($id) {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function getUserByEmail($email) {
        $user = User::where('email', $email);

        if (!$user) {
            return response()->json([
                'success' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    public function getUserBalance(Request $request) {
        $input = $request->input();

        $user_id = $input['user_id'];

        $user = User::find($user_id);

        if (!$user) {

            return response()->json([
                'success' => false,
                'error' => 'User does not exist'
            ]);
        }

        $coinController = new CoinController();
        $allBalance = $coinController->allBalance($user);

        return response()->json([
            'success' => true,
            'balance' => $allBalance
        ]);
    }

    public function getCoinHistory($coin, Request $request) {
        $input = $request->input();

        $user_id = $input['user_id'];

        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User does not exist'
            ]);
        }

        $data = array();
        $controller = new CoinController();
        $data = $controller->transactionHistory($user, $coin);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function addManual($coin, Request $request) {
        $input = $request->input();

        $user = Auth::user();

        if ($user->email != 'sist2m@koin' && $user->email != 'yradmin@sistemkoin.com' && $user->email!='volkan@koin') {
            return response()->json([
                'success' => false,
            ]);
        }
        $user_id = $input['user_id'];
        $amount = $input['amount'];

        $transaction = new Transaction();
        $transaction->user_id = $user_id;
        $transaction->datetime = Carbon::now();
        $transaction->dest_coin = $coin;
        $transaction->dest_amount = round($amount, 8);
        $transaction->type = TransactionType::RECORD;
        $transaction->status = TransactionStatus::SUCCESS;
        $transaction->admin_id = $user->id;
        $transaction->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function deleteManual($coin, Request $request) {
        $input = $request->input();

        $id = $input['id'];
        $tran = Transaction::find($id);
        $tran->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getWithdrawPending($coin, Request $request) {

        $trans = Transaction::where([['src_coin', $coin], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->selectRaw('transactions.id,user_id,to_address,src_amount,fee,is_epay,address,destination_tag,payment_id,transactions.created_at,users.name AS username')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function getWithdrawHistory($coin, Request $request) {
        $trans = Transaction::where([['src_coin', $coin], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::SUCCESS]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->selectRaw('transactions.id, datetime, src_amount, fee, is_epay, txid, users.name AS username')
            ->orderBy('transactions.datetime', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function getDepositHistory($coin, Request $request) {
        $coinInfo = Coin::where('coin', $coin)->first();

        $result = array();
        $trans = Transaction::where([['dest_coin', $coin], ['type', TransactionType::DEPOSIT], ['status', TransactionStatus::SUCCESS]])
                    ->leftJoin('users', 'users.id', 'transactions.user_id')
                    ->select('transactions.*', 'users.name AS username')
                    ->orderBy('transactions.datetime', 'desc')
                    ->get();

        foreach ($trans as $tx) {
            array_push($result, array(
                'username' => $tx->username,
                'datetime' => $tx->datetime,
                'dest_amount' => $tx->dest_amount,
                'txid' => $tx->txid
            ));
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function getFastbuyHistory($coin, Request $request) {
        $trans = Transaction::where([['dest_coin', $coin], ['type', TransactionType::FASTBUY], ['status', TransactionStatus::SUCCESS]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->select('transactions.*', 'users.name AS username')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function getWithdrawDetail($coin, Request $request) {
        $txid = $request->input('txid');

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['txid', $txid]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->select('transactions.*', 'users.name AS username')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function getDepositPending($coin, Request $request) {
        $trans = Transaction::where([['dest_coin', $coin], ['type', TransactionType::DEPOSIT], ['status', TransactionStatus::PENDING]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->select('transactions.*', 'users.name AS username')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function withdrawFinancePending(Request $request) {
        $input = $request->input();

        $id = $input['id'];

        $tran = Transaction::find($id);

        if (!$tran || $tran->type != TransactionType::WITHDRAW) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction Not Found'
            ]);
        }
        if ($tran->is_epay == 1) {
            $ePayController = new EPayController();

            $txid = Carbon::now()->getTimestamp().rand()*1000000000;					//Your Order No.
            $result = $ePayController->payment($tran->to_address, $tran->src_amount - $tran->fee, $tran->src_coin, $txid);

            if (isset($result->RETURN_CODE) && $result->RETURN_CODE == 0 && $result->STATUS == 2) {
                $tran->txid = $txid;
                $tran->epay_order = $result->ORDER_NUM;
                $tran->status = TransactionStatus::SUCCESS;
                $tran->save();
            } else {
                return response()->json([
                    'success' => false,
                    'error' => isset($result->RETURN_MSG) ? $result->RETURN_MSG : 'Error'
                ]);
            }
        } else {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->save();
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function depositFinancePending(Request $request) {
        $input = $request->input();

        $id = $input['id'];

        $tran = Transaction::find($id);

        if (!$tran || $tran->type != TransactionType::DEPOSIT) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction Not Found'
            ]);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function rejectFinanceDeposit(Request $request) {
        $input = $request->input();

        $id = $input['id'];

        $tran = Transaction::find($id);

        if (!$tran || $tran->type != TransactionType::DEPOSIT) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction Not Found'
            ]);
        }

        $tran->status = TransactionStatus::FAIL;
        $tran->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function deleteFinancePending(Request $request) {
        $input = $request->input();

        $id = $input['id'];

        $tran = Transaction::find($id);

        if (!$tran) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction Not Found'
            ]);
        }

        $tran->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function getUserProfile(Request $request) {
        $input = $request->input();

        $profile = Profile::where('user_id', $input['user_id'])->first();

        if (!$profile) {
            $profile = array();
        }
        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    public function verifyUserProfile(Request $request) {
        $input = $request->input();

        $profile = Profile::find($input['id']);

        if (!$profile) {
            return response()->json([
                'success' => false,
            ]);
        }

        foreach ($input as $key => $value) {
            if ($key == 'locale') continue;
            $profile[$key] = $value;
        }

        $profile->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getSettings(Request $request) {
        $input = $request->input();

        $name = $input['name'];

        $settings = AdminSetting::where('name', $name)->get();

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function getUserStatistics(Request $request) {
        $input = $request->input();

        $total_user = User::where('role', '<>', 'admin')->orWhereNull('role')->count();
        $confirmed_user = User::where('confirmed', '1')->where(function($query) {
            $query->where('role', '<>', 'admin');
            $query->orWhereNull('role');
        })->count();

        return response()->json([
            'success' => true,
            'data' => array(
                'total_user' => $total_user,
                'confirmed_user' => $confirmed_user,
                'unconfirmed_user'=> $total_user - $confirmed_user
            )
        ]);
    }

    public function deleteUser(Request $request) {
        $user_id = $request->input('id');

        return response()->json([
            'success' => false,
            'error' => 'User Not Found'
        ]);

        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User Not Found'
            ]);
        }

        $profile = Profile::where('user_id', $user->id);
        if ($profile) {
            $profile->delete();
        }
        $user->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function confirmUser(Request $request) {
        $user_id = $request->input('id');

        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User Not Found'
            ]);
        }
        $user->confirmed = 1;
        $user->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getUserReferral(Request $request)
    {
        $user_id = $request->input('user_id');
        $user = User::find($user_id);
        if (!$user) {

            return response()->json([
                'success' => false,
            ]);
        }

        $refs = User::where('ref_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $refs
        ]);
    }

    public function getRefCommission(Request $request) {
        $user_id = $request->input('user_id');
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
            ]);
        }

        $query = "SELECT dest_coin AS coin, SUM(dest_amount) amount FROM transactions WHERE user_id = {$user->id} AND type = ".TransactionType::BONUS." GROUP BY dest_coin ";

        $data = \DB::select($query);

        $result = array();
        foreach ($data as $record) {
            $result[$record->coin] = $record->amount;
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function getCommissionHistory(Request $request) {
        $user_id = $request->input('user_id');
        $user = User::find($user_id);
        if (!$user) {

            return response()->json([
                'success' => false,
            ]);
        }
        $query =    "SELECT dest_coin AS coin, transactions.*, users.email ".
                    "FROM transactions LEFT JOIN users ON users.id = transactions.ref_id ".
                    "WHERE user_id = {$user->id} AND type = ".TransactionType::BONUS." ".
                    "ORDER BY transactions.id";

        $result = \DB::select($query);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function updateCoinInfo(Request $request) {
        $input = $request->input();

        $coin = Coin::where('coin', $input['coin'])->first();

        if (!$coin) {
            return response()->json([
                'success' => false,
                'error' => 'No Coin'
            ]);
        }

        $coin->withdraw_fee = $input['withdraw_fee'];
        $coin->buy_fee = $input['buy_fee'];
        $coin->sell_fee = $input['sell_fee'];
        $coin->min_withdraw = $input['min_withdraw'];
        $coin->ref_fee = $input['ref_fee'];
        $coin->deposit = $input['deposit'];
        $coin->withdraw = $input['withdraw'];
        $coin->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function getICOHistory($coin, Request $request) {
        $trans = Transaction::where([['dest_coin', $coin], ['type', TransactionType::ICO], ['status', TransactionStatus::SUCCESS]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->select('transactions.*', 'users.name AS username')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function getWithdrawCountByCoin(Request $request) {
        $withdrawType = TransactionType::WITHDRAW;
        $pendingStatus = TransactionStatus::PENDING;

        $query = "SELECT src_coin coin, COUNT(id) count FROM transactions WHERE type = {$withdrawType} AND status = {$pendingStatus} GROUP BY src_coin";

        $result = \DB::select($query);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function closeUser(Request $request) {
        $user_id = $request->input('user_id');

        $user = User::find($user_id);

        if (!$user || $user->close == 1) {
            return response()->json([
                'success' => false,
                'error' => 'User does not exist'
            ]);
        }

        $user->close = 1;
        $user->save();

        $util = new Utility();
        if ($user->remember_token && $user->remember_token != '') {
            try {
                $result = $util->curl_post('https://sistemkoin.com/api/token/logout', array('token' => $user->remember_token));
            } catch (\Exception $e) {
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }


    public function openUser(Request $request) {
        $user_id = $request->input('user_id');

        $user = User::find($user_id);

        if (!$user || $user->close == 0) {
            return response()->json([
                'success' => false,
                'error' => 'User does not exist'
            ]);
        }

        $user->close = 0;
        $user->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getAllWallet($coin, Request $request) {
        if ($coin == 'BTW') {
            $rpcUrl = env('BITWHITE_RPC_URL');

            $utility = new Utility();
            $url = $rpcUrl."accounts/getBalance?address=";

            $result = BitwhiteAddress::all();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'btw_address')->first();

            $data = array();
            foreach ($result as $address) {
                if ($address->address == $admin_address->value) continue;

                $result = $utility->curl_get_contents($url.$address->address);

                if ($result->success && $result->balance  > 0) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : 'admin',
                        'address' => $address->address,
                        'coin_value' => $result->balance * $this::BTW_UNIT
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } else if ($coin == 'XIN') {
            $rpcUrl = env('XIN_RPC_URL');

            $utility = new Utility();

            $addresses = XinAddress::all();

            $users = User::all()->pluck('email', 'id');

            $data = array();
            foreach ($addresses as $address) {
                $url = $rpcUrl."requestType=getAccount&account=".$address->address;
                $result = $utility->curl_get_contents($url);

                if (isset($result->balanceTQT) && $result->balanceTQT > 0) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : 'admin',
                        'address' => $address->address,
                        'coin_value' => $result->balanceTQT * $this::XIN_UNIT
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } else if ($coin == 'USDT') {
            $rpcUrl = env('OMNICORE_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $users = User::all()->pluck('email', 'id');
            $coinAddress = CoinAddress::whereNotNull('usdt_address')->get();

            $data = array();
            foreach ($coinAddress as $address) {
                try {
                    $result = $jsonRpc->omni_getbalance($address->usdt_address, $this::USDT_PROPERTY);
                    if ($result['balance'] > 0) {
                        array_push($data, array(
                            'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : 'admin',
                            'address' => $address->usdt_address,
                            'coin_value' => $result['balance']
                        ));
                    }
                } catch (\Exception $e) {

                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } else if ($coin == 'PIRL') {
            $rpcUrl = env('PIRL_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $addresses = PirlAddress::all();

            $users = User::all()->pluck('email', 'id');

            $result = array();
            foreach ($addresses as $address) {
                $pirl_balance = 0;
                try {
                    $pirl_balance = $jsonRpc->eth_getBalance($address->address, 'latest');
                    $pirl_balance = hexdec($pirl_balance) * $this::PIRL_UNIT;
                }  catch (\Exception $e) {
                    //return response('Error! Please try again.', 403);
                }
                if ($pirl_balance > 0) {
                    array_push($result, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : 'admin',
                        'address' => $address->address,
                        'coin_value' => $pirl_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } else if ($coin == 'BEN') {
            $rpcUrl = env('BITCOEN_RPC_URL');

            $jsonRpc = new BitcoenRPC($rpcUrl);

            $addresses = BitcoenAddress::all();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'ben_address')->first();
            $result = array();

            foreach ($addresses as $address) {
                if ($address->address == $admin_address->value) continue;

                $ben_balance = 0;

                try {
                    $wallet = $jsonRpc->getWalletInfo($address->address);
                    $ben_balance = BitcoenRPC::mil2Ben($wallet['balance']);
                } catch (\Exception $e) {
                    continue;
                }

                if ($ben_balance > 0) {
                    array_push($result, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->address,
                        'coin_value' => $ben_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } else if ($coin == 'TRX') {
            $rpcUrl = env('TRX_FULLNODE_RPC_URL');

            $addresses = TronAddress::all();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'trx_address')->first();
            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                if ($address->address == $admin_address->value) continue;

                $trx_balance = 0;

                try {
                    $url = $rpcUrl.'wallet/getaccount';
                    $params = array(
                        'address' => $address->hexAddress
                    );
                    $result = $util->curl_post($url, $params);
                    if (isset($result->balance)) {
                        $trx_balance = $result->balance * $this::TRX_UNIT;
                    }
                } catch (\Exception $e) {
                    continue;
                }

                if ($trx_balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->address,
                        'coin_value' => $trx_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }  else if ($coin == 'IGG') {
            $addresses = TronAddress::where('igg_deposited', 1)->get();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'trx_address')->first();
            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                if ($address->address == $admin_address->value) continue;

                $balance = 0;

                try {
                    $url = 'https://super.guildchat.io/wallet/triggersmartcontract';
                    $abiEncoder =  str_pad(substr($address->hexAddress, 2), 64, "0", STR_PAD_LEFT);

                    $params = array(
                        'contract_address' => env($coin.'_CONTRACT_HEX_ADDRESS'),
                        'function_selector' => 'balanceOf(address)',
                        'parameter' => $abiEncoder,
                        'fee_limit' => 1000000,
                        'call_value' => 0,
                        'owner_address' => $address->hexAddress
                    );

                    $result = $util->curl_post($url, $params);

                    if($result->result->result) {
                        $balance = hexdec('0x'.$result->constant_result[0]) / pow(10, $this::TRC20_DECIMALS[$coin]);
                    }
                } catch (\Exception $e) {
                    continue;
                }

                if ($balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->address,
                        'coin_value' => $balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }  else if ($coin == 'BTT') {
            $rpcUrl = env('TRX_FULLNODE_RPC_URL');

            $addresses = TronAddress::where('btt_deposited', 1)->get();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'trx_address')->first();
            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                if ($address->address == $admin_address->value) continue;

                $balance = 0;

                try {
                    $url = $rpcUrl.'wallet/getaccount';
                    $params = array(
                        'address' => $address->hexAddress
                    );
                    $result = $util->curl_post($url, $params);
                    if (isset($result->assetV2)) {
                        foreach ($result->assetV2 as $asset) {
                            if ($asset->key == env('BTT_ASSET_ID')) {
                                $balance = $asset->value / pow(10, $this::TRC10_DECIMALS['BTT']);
                                break;
                            }
                        }

                    }
                } catch (\Exception $e) {
                    continue;
                }

                if ($balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->address,
                        'coin_value' => $balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }  else if ($coin == 'NTY') {
            $rpcUrl = env('NTY_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $addresses = NextyAddress::all();

            $users = User::all()->pluck('email', 'id');

            $result = array();
            foreach ($addresses as $address) {
                $nty_balance = 0;
                try {
                    $nty_balance = $jsonRpc->eth_getBalance($address->address, 'latest');
                    $nty_balance = hexdec($nty_balance) * $this::NTY_UNIT;
                }  catch (\Exception $e) {
                    //return response('Error! Please try again.', 403);
                }
                if ($nty_balance > 0) {
                    array_push($result, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : 'admin',
                        'address' => $address->address,
                        'coin_value' => $nty_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } else if ($coin == 'WAVES') {
            $rpcUrl = env('WAVES_RPC_URL');

            $addresses = CoinAddress::whereNotNull('waves_address')->get();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'waves_address')->first();
            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                if ($address->waves_address == $admin_address->value) continue;

                $waves_balance = 0;

                $url = $rpcUrl.'addresses/balance/'.$address->waves_address;

                try {
                    $result = $util->curl_get_contents($url);
                    if (isset($result->balance)) {
                        $waves_balance = $result->balance * $this::WAVES_UNIT;
                    }
                } catch (\Exception $e) {
                    $waves_balance = 0;
                }


                if ($waves_balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->waves_address,
                        'coin_value' => $waves_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } else if ($coin == 'CLB') {
            $rpcUrl = env('WAVES_RPC_URL');

            $addresses = CoinAddress::whereNotNull('waves_address')->get();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'waves_address')->first();
            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                if ($address->waves_address == $admin_address->value) continue;

                $waves_balance = 0;

                $url = $rpcUrl.'assets/balance/'.$address->waves_address.'/'.$this::CLB_ASSETID;

                try {
                    $result = $util->curl_get_contents($url);
                    if (isset($result->balance)) {
                        $waves_balance = $result->balance * $this::CLB_UNIT;
                    }
                } catch (\Exception $e) {
                    $waves_balance = 0;
                }

                if ($waves_balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->waves_address,
                        'coin_value' => $waves_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } else if ($coin == 'ADMN') {
            $rpcUrl = env('WAVES_RPC_URL');

            $addresses = CoinAddress::whereNotNull('waves_address')->get();

            $users = User::all()->pluck('email', 'id');

            $admin_address = AdminSetting::where('name', 'waves_address')->first();
            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                if ($address->waves_address == $admin_address->value) continue;

                $waves_balance = 0;

                $url = $rpcUrl.'assets/balance/'.$address->waves_address.'/'.$this::ADMN_ASSETID;

                try {
                    $result = $util->curl_get_contents($url);
                    if (isset($result->balance)) {
                        $waves_balance = $result->balance * $this::ADMN_UNIT;
                    }
                } catch (\Exception $e) {
                    $waves_balance = 0;
                }

                if ($waves_balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->waves_address,
                        'coin_value' => $waves_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } else if ($coin == 'ONT') {
            $rpcUrl = env('ONT_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $addresses = ONTAddress::all();

            $users = User::all()->pluck('email', 'id');

            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                $ont_balance = 0;

                try {
                    $result = $jsonRpc->getbalance($address->address);
                    if (isset($result['ont'])) {
                        $ont_balance = $result['ont'] * $this::ONT_UNIT;
                    }
                } catch (\Exception $e) {
                    continue;
                }

                if ($ont_balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->address,
                        'coin_value' => $ont_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } else if ($coin == 'ONG') {
            $rpcUrl = env('ONT_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $addresses = ONTAddress::all();

            $users = User::all()->pluck('email', 'id');

            $data = array();

            $util = new Utility();
            foreach ($addresses as $address) {
                $ong_balance = 0;

                try {
                    $result = $jsonRpc->getbalance($address->address);
                    if (isset($result['ong'])) {
                        $ong_balance = $result['ong'] * $this::ONG_UNIT;
                    }
                } catch (\Exception $e) {
                    continue;
                }

                if ($ong_balance > 0.1) {
                    array_push($data, array(
                        'email' => $address->user_id > 1 && isset($users[$address->user_id]) ? $users[$address->user_id] : '',
                        'address' => $address->address,
                        'coin_value' => $ong_balance
                    ));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        }

        return response()->json([
            'success' => false,
        ]);
    }

    public function getAllCoinPairs() {
        $pairs = CoinPair::all();

        return response()->json([
            'success' => true,
            'data' => $pairs
        ]);
    }

    public function suspendCoinPair(Request $request) {
        $user = Auth::user();

        if ($user->email != 'sist2m@koin' && $user->email != 'yradmin@sistemkoin.com' && $user->email != 'vandykeeee@gmail.com' && $user->email != 'volkan@koin') {
            return response()->json([
                'success' => false,
            ]);
        }

        $id = $request->input('id');

        $coinPair = CoinPair::find($id);

        if ($coinPair && $coinPair->suspended == 0) {
            $coinPair->suspended = 1;
            $coinPair->save();

            return response()->json([
                'success' => true,
            ]);
        }
        return response()->json([
            'success' => false,
            'error' => 'You cannot suspend this pair'
        ]);
    }

    public function unsuspendCoinPair(Request $request) {
        $user = Auth::user();

        if ($user->email != 'sist2m@koin' && $user->email != 'yradmin@sistemkoin.com' && $user->email != 'vandykeeee@gmail.com' && $user->email != 'volkan@koin') {
            return response()->json([
                'success' => false,
            ]);
        }

        $id = $request->input('id');

        $coinPair = CoinPair::find($id);

        if ($coinPair && $coinPair->suspended == 1) {
            $coinPair->suspended = 0;
            $coinPair->save();

            return response()->json([
                'success' => true,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'You cannot unsuspend this pair'
        ]);
    }

    public function getWalletStatistics(Request $request) {
        $user = Auth::user();

        if ($user->email != 'sist2m@koin' && $user->email != 'yradmin@sistemkoin.com') {
            return response()->json([
                'success' => false,
            ]);
        }

        $dest_query =   "SELECT dest_coin AS coin, SUM(dest_amount) amount FROM transactions, users WHERE transactions.user_id = users.id AND users.role = 'user' AND status = ".TransactionStatus::SUCCESS." GROUP BY dest_coin";
        $src_query =   "SELECT src_coin AS coin, SUM(src_amount) amount FROM transactions, users WHERE transactions.user_id = users.id AND users.role = 'user' AND status <> ".TransactionStatus::FAIL." GROUP BY src_coin";
        $open_query = "SELECT src_coin AS coin, SUM(src_amount) amount FROM orders, users WHERE orders.user_id = users.id AND users.role = 'user' GROUP BY src_coin";

        $query =    "SELECT coins.coin, coins.fullname, coins.is_erc20, src.amount AS src_amount, dest.amount AS dest_amount, open.amount AS open_amount ".
                    "FROM coins LEFT JOIN (".$src_query.") AS src ON coins.coin = src.coin ".
                        "LEFT JOIN (".$dest_query.") AS dest ON coins.coin = dest.coin ".
                        "LEFT JOIN (".$open_query.") AS open ON coins.coin = open.coin ".
                    "WHERE coins.is_crypto = 1 ";

        $result = \DB::select($query);

        foreach ($result as $coin) {
            $coin->user_amount = $coin->dest_amount - $coin->src_amount;

            $walletController = new WalletController();
            $walletAmount = 0;
            
            if ($coin->is_erc20 == 1) {
                $walletAmount = $walletController->getErc20TotalBalance($coin->coin);
            } else {
                switch ($coin->coin) {
                    case 'BTC':
                        $walletAmount = $walletController->getBTCTotalBalance();
                        break;
                    case 'ETH':
                        $walletAmount = $walletController->getETHTotalBalance();
                        break;
                    case 'XRP':
                        $walletAmount = $walletController->getXRPTotalBalance();
                        break;
                    case 'LTC':
                        $walletAmount = $walletController->getLTCTotalBalance();
                        break;
                    case 'DOGE':
                        $walletAmount = $walletController->getDOGETotalBalance();
                        break;
                    case 'BCH':
                        $walletAmount = $walletController->getBCHTotalBalance();
                        break;
                    case 'BTG':
                        $walletAmount = $walletController->getBTGTotalBalance();
                        break;
                    case 'ZEC':
                        $walletAmount = $walletController->getZECTotalBalance();
                        break;
                    case 'DGB':
                        $walletAmount = $walletController->getDGBTotalBalance();
                        break;
                    case 'XMR':
                        $walletAmount = $walletController->getXMRTotalBalance();
                        break;
                    case 'XLM':
                        $walletAmount = $walletController->getXLMTotalBalance();
                        break;
                    case 'BEN':
                        $walletAmount = $walletController->getBENTotalBalance();
                        break;
                    case 'XIN':
                        $walletAmount = $walletController->getXINTotalBalance();
                        break;
                    case 'FRST':
                        $walletAmount = $walletController->getFRSTTotalBalance();
                        break;
                    case 'USDT':
                        $walletAmount = $walletController->getUSDTTotalBalance();
                        break;
                    case 'GRS':
                        $walletAmount = $walletController->getGRSTotalBalance();
                        break;
                    case 'SYS':
                        $walletAmount = $walletController->getSYSTotalBalance();
                        break;
                    case 'XSN':
                        $walletAmount = $walletController->getXSNTotalBalance();
                        break;
                    case 'BTW':
                        $walletAmount = $walletController->getBTWTotalBalance();
                        break;
                    case 'MEDIC':
                        $walletAmount = $walletController->getMEDICTotalBalance();
                        break;
                    case 'BTCP':
                        $walletAmount = $walletController->getBTCPTotalBalance();
                        break;
                    case 'PIRL':
                        $walletAmount = $walletController->getPIRLTotalBalance();
                        break;
                    case 'TPAY':
                        $walletAmount = $walletController->getTPAYTotalBalance();
                        break;
                    case 'BTS':
                        $walletAmount = $walletController->getBTSTotalBalance();
                        break;
                    case 'CVN':
                        $walletAmount = $walletController->getCVNTotalBalance();
                        break;
                    case 'DEEX':
                        $walletAmount = $walletController->getDEEXTotalBalance();
                        break;
                    case 'HTML':
                        $walletAmount = $walletController->getHTMLTotalBalance();
                        break;
                    case 'XVG':
                        $walletAmount = $walletController->getXVGTotalBalance();
                        break;
                    case 'ACT':
                        $walletAmount = $walletController->getACTTotalBalance();
                        break;
                    case 'VEX':
                        $walletAmount = $walletController->getVEXTotalBalance();
                        break;
                    case 'XGOX':
                        $walletAmount = $walletController->getXGOXTotalBalance();
                        break;
                    case 'PAC':
                        $walletAmount = $walletController->getPACTotalBalance();
                        break;
                    case 'MRI':
                        $walletAmount = $walletController->getMRITotalBalance();
                        break;
                    case 'DASH':
                        $walletAmount = $walletController->getDASHTotalBalance();
                        break;
                    case 'TRX':
                        $walletAmount = $walletController->getTRXTotalBalance();
                        break;
                    case 'CBC':
                        $walletAmount = $walletController->getCBCTotalBalance();
                        break;
                    case 'XZC':
                        $walletAmount = $walletController->getXZCTotalBalance();
                        break;
                    case 'NTY':
                        $walletAmount = $walletController->getNTYTotalBalance();
                        break;
                    case 'ZEN':
                        $walletAmount = $walletController->getZENTotalBalance();
                        break;
                    case 'GXX':
                        $walletAmount = $walletController->getGXXTotalBalance();
                        break;
                    case 'BZX':
                        $walletAmount = $walletController->getBZXTotalBalance();
                        break;
                    case 'OOT':
                        $walletAmount = $walletController->getOOTTotalBalance();
                        break;
                    case 'ABBC':
                        $walletAmount = $walletController->getABBCTotalBalance();
                        break;
                    case 'WIRE':
                        $walletAmount = $walletController->getWIRETotalBalance();
                        break;
                    case 'CLO':
                        $walletAmount = $walletController->getCLOTotalBalance();
                        break;
                    case 'WAVES':
                        $walletAmount = $walletController->getWAVESTotalBalance();
                        break;
                    case 'CLB':
                        $walletAmount = $walletController->getCLBTotalBalance();
                        break;
                    case 'ADMN':
                        $walletAmount = $walletController->getADMNTotalBalance();
                        break;
                    case 'LGS':
                        $walletAmount = $walletController->getLGSTotalBalance();
                        break;
                    case 'ETN':
                        $walletAmount = $walletController->getETNTotalBalance();
                        break;
                    case 'STK':
                        $walletAmount = $walletController->getSTKTotalBalance();
                        break;
                    case 'SCR':
                        $walletAmount = $walletController->getSCRTotalBalance();
                        break;
                    case 'XRC':
                        $walletAmount = $walletController->getXRCTotalBalance();
                        break;
                    case 'ONT':
                        $walletAmount = $walletController->getONTTotalBalance();
                        break;
                    case 'ONG':
                        $walletAmount = $walletController->getONGTotalBalance();
                        break;
                    case 'GBX':
                        $walletAmount = $walletController->getGBXTotalBalance();
                        break;
                    case 'CCX':
                        $walletAmount = $walletController->getCCXTotalBalance();
                        break;
                    case 'TMC':
                        $walletAmount = $walletController->getTMCTotalBalance();
                        break;
                    case 'IGG':
                        $walletAmount = $walletController->getTRC20TotalBalance($coin);
                        break;
                    case 'BTT':
                        $walletAmount = $walletController->getTRC10TotalBalance($coin);
                        break;
                    case 'BITG':
                        $walletAmount = $walletController->getBITGTotalBalance();
                        break;
                    case 'EXO':
                        $walletAmount = $walletController->getEXOTotalBalance();
                        break;
                    case 'BTCONE':
                        $walletAmount = $walletController->getBTCONETotalBalance();
                        break;
                    case 'SINS':
                        $walletAmount = $walletController->getSINSTotalBalance();
                        break;
                    case 'BRAVO':
                        $walletAmount = $walletController->getBRAVOTotalBalance();
                        break;
                    case 'MPG':
                        $walletAmount = $walletController->getMPGTotalBalance();
                        break;
                    case 'BTK':
                        $walletAmount = $walletController->getBTKTotalBalance();
                        break;
                    case 'VIC':
                        $walletAmount = $walletController->getVICTotalBalance();
                        break;
                    case 'TGN':
                        $walletAmount = $walletController->getTGNTotalBalance();
                        break;
                    case 'QC':
                        $walletAmount = $walletController->getQCTotalBalance();
                        break;
                    case 'BCZ':
                        $walletAmount = $walletController->getBCZTotalBalance();
                        break;
                    case 'CMM':
                        $walletAmount = $walletController->getCMMTotalBalance();
                        break;
                    case 'CDZC':
                        $walletAmount = $walletController->getCDZCTotalBalance();
                        break;
                }
            }

            if (!isset($coin->open_amount) || is_null($coin->open_amount)) {
                $coin->open_amount = 0;
            }
            $coin->wallet_amount = $walletAmount;
            $coin->difference_amount = $coin->user_amount - $coin->open_amount - $coin->wallet_amount;
        }


        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function getCompetition($coin, $marketCoin, Request $request) {
        $pair = CoinPair::where([['coin', $coin], ['market_coin', $marketCoin]])->first();

        if (!$pair) {
            return response()->json([
                'success' => true,
                'data' => false
            ]);
        }

        $input = $request->input();

        $startdate = $input['startdate'];
        $enddate = $input['enddate'];

        $sql =  "SELECT users.email, SUM(IF(src_coin = '{$pair->coin}', transactions.src_amount, transactions.dest_amount)) amount ".
                "FROM transactions LEFT JOIN users ON transactions.user_id = users.id ".
                "WHERE transactions.type = 2 AND transactions.status = 1 ".
                    " AND ((transactions.src_coin = '{$pair->coin}' AND transactions.dest_coin ='{$pair->market_coin}') OR (transactions.dest_coin = '{$pair->coin}' AND transactions.src_coin ='{$pair->market_coin}')) ".
                    " AND datetime >= '{$startdate}' AND datetime <= '{$enddate}' ".
                    " AND users.role = 'user' ".
                "GROUP BY users.email ".
                "ORDER BY amount DESC ".
                "LIMIT 50";

        $result = \DB::select($sql);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }


    public function getAllIeoCoinPairs() {
        $pairs = IEOCoinPair::all();

        return response()->json([
            'success' => true,
            'data' => $pairs
        ]);
    }

    public function suspendIeoCoinPair(Request $request) {
        $user = Auth::user();

        if ($user->email != 'sist2m@koin' && $user->email != 'yradmin@sistemkoin.com' && $user->email != 'vandykeeee@gmail.com' && $user->email != 'volkan@koin') {
            return response()->json([
                'success' => false,
            ]);
        }

        $id = $request->input('id');

        $coinPair = IEOCoinPair::find($id);

        if ($coinPair && $coinPair->suspended == 0) {
            $coinPair->suspended = 1;
            $coinPair->save();

            return response()->json([
                'success' => true,
            ]);
        }
        return response()->json([
            'success' => false,
            'error' => 'You cannot suspend this pair'
        ]);
    }

    public function unsuspendIeoCoinPair(Request $request) {
        $user = Auth::user();

        if ($user->email != 'sist2m@koin' && $user->email != 'yradmin@sistemkoin.com' && $user->email != 'vandykeeee@gmail.com' && $user->email != 'volkan@koin') {
            return response()->json([
                'success' => false,
            ]);
        }

        $id = $request->input('id');

        $coinPair = IEOCoinPair::find($id);

        if ($coinPair && $coinPair->suspended == 1) {
            $coinPair->suspended = 0;
            $coinPair->save();

            return response()->json([
                'success' => true,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'You cannot unsuspend this pair'
        ]);
    }
}
