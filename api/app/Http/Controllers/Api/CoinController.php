<?php

namespace App\Http\Controllers\Api;

use App\Entities\Coin;
use App\Entities\CoinAddress;
use App\Entities\ConfirmWithdraw;
use App\Entities\Order;
use App\Entities\Profile;
use App\Entities\Transaction;
use App\Events\SendEmailEvent;
use App\Http\Controllers\Controller;
use App\Libs\ArraySort;
use App\Libs\Utility;
use App\Mail\ConfirmWithdrawMail;
use Carbon\Carbon;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoinController extends Controller
{
    public function allBalance($user) {
        $successType = TransactionStatus::SUCCESS;
        $failType = TransactionStatus::FAIL;
        $query =    "SELECT coins.coin, IFNULL(tran_plus.amount, 0) - IFNULL(tran_minus.amount, 0) - IFNULL(order_minus.amount, 0) AS amount ".
                    "FROM coins ".
                        "LEFT JOIN (SELECT dest_coin AS coin, SUM(dest_amount) amount FROM transactions WHERE user_id = {$user->id} AND status = {$successType} GROUP BY dest_coin) tran_plus ON coins.coin = tran_plus.coin ".
                        "LEFT JOIN (SELECT src_coin AS coin, SUM(src_amount) amount FROM transactions WHERE user_id = {$user->id} AND status <> {$failType} GROUP BY src_coin) tran_minus ON coins.coin = tran_minus.coin ".
                        "LEFT JOIN (SELECT src_coin AS coin, SUM(src_amount) amount FROM orders WHERE user_id = {$user->id} GROUP BY src_coin) order_minus ON coins.coin = order_minus.coin ";
        $result = \DB::select($query);

        $balance = array();
        foreach ($result as $record) {
            $balance[$record->coin] = round($record->amount, 8);
        }

        return $balance;
    }

    public function balance($user, $coin) {
        $tran_plus = Transaction::where([['user_id', $user->id], ['dest_coin', $coin], ['status', TransactionStatus::SUCCESS]])->sum('dest_amount');
        $tran_minus = Transaction::where([['user_id', $user->id], ['src_coin', $coin], ['status', '<>', TransactionStatus::FAIL]])->sum('src_amount');
        $order_plus = Order::where([['user_id', $user->id], ['src_coin', $coin]])->sum('src_amount');

        return round($tran_plus - $tran_minus - $order_plus, 8);
    }

    public function getBalance($coin, Request $request) {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'balance' => $this->balance($user, $coin),
        ]);
    }

    public function createWallet($coin, Request $request) {
        $user = Auth::user();

        $coinAddress = CoinAddress::where('user_id', $user->id)->first();

        if (!$coinAddress) {
            $coinAddress = new CoinAddress();
            $coinAddress->user_id = $user->id;
            $coinAddress->save();
        }

        $walletController = new WalletController();

        $coinInfo = Coin::where('coin', $coin)->first();
        if ($coinInfo->is_erc20 == 1) {
            $result = $walletController->createETHWallet($user, $coinAddress);
            return response()->json([
                'success' => true,
                'address' => $result
            ]);
        }

        $result = false;
        switch ($coin) {
            case 'BTC':
                $result = $walletController->createBTCWallet($user, $coinAddress);
                break;
            case 'ETH':
                $result = $walletController->createETHWallet($user, $coinAddress);
                break;
            case 'XRP':
                $result = $walletController->createXRPWallet($user, $coinAddress);
                break;
            case 'LTC':
                $result = $walletController->createLTCWallet($user, $coinAddress);
                break;
            case 'DOGE':
                $result = $walletController->createDOGEWallet($user, $coinAddress);
                break;
            case 'BCH':
                $result = $walletController->createBCHWallet($user, $coinAddress);
                break;
            case 'BTG':
                $result = $walletController->createBTGWallet($user, $coinAddress);
                break;
            case 'ZEC':
                $result = $walletController->createZECWallet($user, $coinAddress);
                break;
            case 'DGB':
                $result = $walletController->createDGBWallet($user, $coinAddress);
                break;
            case 'XMR':
                $result = $walletController->createXMRWallet($user, $coinAddress);
                break;
            case 'XLM':
                $result = $walletController->createXLMWallet($user, $coinAddress);
                break;
            case 'BEN':
                $result = $walletController->createBENWallet($user, $coinAddress);
                break;
            case 'XIN':
                $result = $walletController->createXINWallet($user, $coinAddress);
                break;
            case 'FRST':
                $result = $walletController->createFRSTWallet($user, $coinAddress);
                break;
            case 'USDT':
                $result = $walletController->createUSDTWallet($user, $coinAddress);
                break;
            case 'GRS':
                $result = $walletController->createGRSWallet($user, $coinAddress);
                break;
            case 'SYS':
                $result = $walletController->createSYSWallet($user, $coinAddress);
                break;
            case 'XSN':
                $result = $walletController->createXSNWallet($user, $coinAddress);
                break;
            case 'BTW':
                $result = $walletController->createBTWWallet($user, $coinAddress);
                break;
            case 'MEDIC':
                $result = $walletController->createMEDICWallet($user, $coinAddress);
                break;
            case 'BTCP':
                $result = $walletController->createBTCPWallet($user, $coinAddress);
                break;
            case 'PIRL':
                $result = $walletController->createPIRLWallet($user, $coinAddress);
                break;
            case 'TPAY':
                $result = $walletController->createTPAYWallet($user, $coinAddress);
                break;
            case 'BTS':
                $result = $walletController->createBTSWallet($user, $coinAddress);
                break;
            case 'CVN':
                $result = $walletController->createBTSWallet($user, $coinAddress);
                break;
            case 'DEEX':
                $result = $walletController->createBTSWallet($user, $coinAddress);
                break;
            case 'HTML':
                $result = $walletController->createHTMLWallet($user, $coinAddress);
                break;
            case 'XVG':
                $result = $walletController->createXVGWallet($user, $coinAddress);
                break;
            case 'ACT':
                $result = $walletController->createACTWallet($coinAddress);
                break;
            case 'VEX':
                $result = $walletController->createACTWallet($coinAddress);
                break;
            case 'XGOX':
                $result = $walletController->createXGOXWallet($user, $coinAddress);
                break;
            case 'PAC':
                $result = $walletController->createPACWallet($user, $coinAddress);
                break;
            case 'MRI':
                $result = $walletController->createMRIWallet($user, $coinAddress);
                break;
            case 'DASH':
                $result = $walletController->createDASHWallet($user, $coinAddress);
                break;
            case 'TRX':case 'IGG':case 'BTT':
                $result = $walletController->createTRXWallet($user, $coinAddress);
                break;
            case 'CBC':
                $result = $walletController->createCBCWallet($user, $coinAddress);
                break;
            case 'XZC':
                $result = $walletController->createXZCWallet($user, $coinAddress);
                break;
            case 'NTY':
                $result = $walletController->createNTYWallet($user, $coinAddress);
                break;
            case 'ZEN':
                $result = $walletController->createZENWallet($user, $coinAddress);
                break;
            case 'GXX':
                $result = $walletController->createGXXWallet($user, $coinAddress);
                break;
            case 'BZX':
                $result = $walletController->createBZXWallet($user, $coinAddress);
                break;
            case 'OOT':
                $result = $walletController->createOOTWallet($user, $coinAddress);
                break;
            case 'ABBC':
                $result = $walletController->createABBCWallet($user, $coinAddress);
                break;
            case 'WIRE':
                $result = $walletController->createWIREWallet($user, $coinAddress);
                break;
            case 'CLO':
                $result = $walletController->createCLOWallet($user, $coinAddress);
                break;
            case 'WAVES':
                $result = $walletController->createWAVESWallet($user, $coinAddress);
                break;
            case 'CLB':
                $result = $walletController->createWAVESWallet($user, $coinAddress);
                break;
            case 'ADMN':
                $result = $walletController->createWAVESWallet($user, $coinAddress);
                break;
            case 'LGS':
                $result = $walletController->createLGSWallet($user, $coinAddress);
                break;
            case 'ETN':
                $result = $walletController->createETNWallet($user, $coinAddress);
                break;
            case 'STK':
                $result = $walletController->createSTKWallet($user, $coinAddress);
                break;
            case 'SCR':
                $result = $walletController->createSCRWallet($user, $coinAddress);
                break;
            case 'XRC':
                $result = $walletController->createXRCWallet($user, $coinAddress);
                break;
            case 'ONT':
                $result = $walletController->createONTWallet($user, $coinAddress);
                break;
            case 'ONG':
                $result = $walletController->createONTWallet($user, $coinAddress);
                break;
            case 'GBX':
                $result = $walletController->createGBXWallet($user, $coinAddress);
                break;
            case 'CCX':
                $result = $walletController->createCCXWallet($user, $coinAddress);
                break;
            case 'TMC':
                $result = $walletController->createTMCWallet($user, $coinAddress);
                break;
            case 'BITG':
                $result = $walletController->createBITGWallet($user, $coinAddress);
                break;
            case 'EXO':
                $result = $walletController->createEXOWallet($user, $coinAddress);
                break;
            case 'BTCONE':
                $result = $walletController->createBTCONEWallet($user, $coinAddress);
                break;
            case 'SINS':
                $result = $walletController->createSINSWallet($user, $coinAddress);
                break;
            case 'BRAVO':
                $result = $walletController->createBRAVOWallet($user, $coinAddress);
                break;
            case 'MPG':
                $result = $walletController->createMPGWallet($user, $coinAddress);
                break;
            case 'BTK':
                $result = $walletController->createBTKWallet($user, $coinAddress);
                break;
            case 'VIC':
                $result = $walletController->createVICWallet($user, $coinAddress);
                break;
            case 'TGN':
                $result = $walletController->createTGNWallet($user, $coinAddress);
                break;
            case 'QC':
                $result = $walletController->createQCWallet($user, $coinAddress);
                break;
            case 'BCZ':
                $result = $walletController->createBCZWallet($user, $coinAddress);
                break;
            case 'CMM':
                $result = $walletController->createCMMWallet($user, $coinAddress);
                break;
            case 'CDZC':
                $result = $walletController->createCDZCWallet($user, $coinAddress);
                break;
        }

        if ($result == false) {
            return response()->json([
                'success' => false,
                'error' => 'Wallet is closed temporarily. Please try later.'
            ]);
        }
        return response()->json([
            'success' => true,
            'address' => $result
        ]);
    }

    public function generateAdminAddress($coin, Request $request) {
        $input = $request->input();

        $walletController = new WalletController();

        return response()->json([
            'success' => false,
            'error' => 'Admin Wallet is closed temporarily. Please try later.'
        ]);
    }

    public function requestWithdraw($coin, Request $request) {
        $user = Auth::user();

        if ($user->close == 1) {
            return response()->json(['error' => 'Account is closed', 'success' => false]);
        }

        $profile = Profile::where('user_id', $user->id)->first();

        if ($profile->verify_status != 3) {
            return response()->json(['error' => 'Please verify your account.', 'success' => false]);
        }

        $input = $request->input();

        $address = $input['address'];
        $amount = round($input['amount'], 8);
        $destination_tag = isset($input['destination_tag']) ? $input['destination_tag'] : null;
        $memo = isset($input['memo']) ? $input['memo'] : null;
        $publicKey = isset($input['public_key']) ? $input['public_key'] : null;
        $paymentId = isset($input['paymentid']) ? $input['paymentid'] : null;
        $msg = isset($input['msg']) ? $input['msg'] : null;

        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Amount must be greater than 0'
            ]);
        }

        $walletController = new WalletController();
        $result = false;

        $coinInfo = Coin::where('coin', $coin)->first();
        if ($coinInfo->is_erc20 == 1) {
            $result = $walletController->validateETHAddress($address);
        }

        switch ($coin) {
            case 'BTC':
                $result = $walletController->validateBTCAddress($address);
                break;
            case 'ETH':
                $result = $walletController->validateETHAddress($address);
                break;
            case 'XRP':
                $result = true;
                break;
            case 'LTC':
                $result = $walletController->validateLTCAddress($address);
                break;
            case 'DOGE':
                $result = $walletController->validateDOGEAddress($address);
                break;
            case 'BCH':
                $result = $walletController->validateBCHAddress($address);
                break;
            case 'BTG':
                $result = $walletController->validateBTGAddress($address);
                break;
            case 'ZEC':
                $result = $walletController->validateZECAddress($address);
                break;
            case 'DGB':
                $result = $walletController->validateDGBAddress($address);
                break;
            case 'XMR':
                $result = $walletController->validateXMRAddress($address);
                break;
            case 'XLM':
                $result = $walletController->validateXLMAddress($address);
                break;
            case 'BEN':
                $result = $walletController->validateBENAddress($address);
                break;
            case 'XIN':
                $result = $walletController->validateXINAddress($address, $publicKey);
                break;
            case 'FRST':
                $result = $walletController->validateFRSTAddress($address);
                break;
            case 'USDT':
                $result = $walletController->validateUSDTAddress($address);
                break;
            case 'GRS':
                $result = $walletController->validateGRSAddress($address);
                break;
            case 'SYS':
                $result = $walletController->validateSYSAddress($address);
                break;
            case 'XSN':
                $result = $walletController->validateXSNAddress($address);
                break;
            case 'BTW':
                $result = $walletController->validateBTWAddress($address);
                break;
            case 'MEDIC':
                $result = $walletController->validateMEDICAddress($address);
                break;
            case 'BTCP':
                $result = $walletController->validateBTCPAddress($address);
                break;
            case 'PIRL':
                $result = $walletController->validatePIRLAddress($address);
                break;
            case 'TPAY':
                $result = $walletController->validateTPAYAddress($address);
                break;
            case 'BTS': case 'CVN': case 'DEEX':case 'SCR':case 'BRAVO':case 'MPG':case 'TGN':             
                $result = true;
                break;
            case 'HTML':
                $result = $walletController->validateHTMLAddress($address);
                break;
            case 'XVG':
                $result = $walletController->validateXVGAddress($address);
                break;
            case 'ACT':
                $result = $walletController->validateACTAddress($address);
                break;
            case 'VEX':
                $result = $walletController->validateACTAddress($address);
                break;
            case 'XGOX':
                $result = $walletController->validateXGOXAddress($address);
                break;
            case 'PAC':
                $result = $walletController->validatePACAddress($address);
                break;
            case 'MRI':
                $result = $walletController->validateMRIAddress($address);
                break;
            case 'DASH':
                $result = $walletController->validateDASHAddress($address);
                break;
            case 'TRX':case 'IGG':case 'BTT':
                $result = true;
                break;
            case 'CBC':
                $result = $walletController->validateCBCAddress($address);
                break;
            case 'XZC':
                $result = $walletController->validateXZCAddress($address);
                break;
            case 'NTY':
                $result = $walletController->validateNTYAddress($address);
                break;
            case 'ZEN':
                $result = $walletController->validateZENAddress($address);
                break;
            case 'GXX':
                $result = $walletController->validateGXXAddress($address);
                break;
            case 'BZX':
                $result = $walletController->validateBZXAddress($address);
                break;
            case 'OOT':
                $result = $walletController->validateOOTAddress($address);
                break;
            case 'ABBC':
                $result = $walletController->validateABBCAddress($address);
                break;
            case 'WIRE':
                $result = $walletController->validateWIREAddress($address);
                break;
            case 'CLO':
                $result = $walletController->validateETHAddress($address);
                break;
            case 'WAVES':
                $result = true;
                break;
            case 'CLB':case 'ADMN':
                $result = true;
                break;
            case 'LGS':
                $result = $walletController->validateLGSAddress($address);
                break;
            case 'ETN':
                $result = $walletController->validateETNAddress($address);
                break;
            case 'STK':
                $result = $walletController->validateSTKAddress($address);
                break;
            case 'XRC':
                $result = $walletController->validateXRCAddress($address);
                break;
            case 'ONT':
                $result = $walletController->validateONTAddress($address);
                break;
            case 'ONG':
                $result = $walletController->validateONTAddress($address);
                break;
            case 'GBX':
                $result = $walletController->validateGBXAddress($address);
                break;
            case 'CCX':
                $result = $walletController->validateCCXAddress($address);
                break;
            case 'TMC':
                $result = $walletController->validateTMCAddress($address);
                break;
            case 'BITG':
                $result = $walletController->validateBITGAddress($address);
                break;
            case 'EXO':
                $result = $walletController->validateEXOAddress($address);
                break;
            case 'BTCONE':
                $result = $walletController->validateBTCONEAddress($address);
                break;
            case 'SINS':
                $result = $walletController->validateSINSAddress($address);
                break;
            case 'BTK':
                $result = $walletController->validateBTKAddress($address);
                break;
            case 'VIC':
                $result = $walletController->validateVICAddress($address);
                break;
            case 'QC':
                $result = $walletController->validateQCAddress($address);
                break;
            case 'BCZ':
                $result = $walletController->validateBCZAddress($address);
                break;
            case 'CMM':
                $result = $walletController->validateCMMAddress($address);
                break;
            case 'CDZC':
                $result = $walletController->validateCDZCAddress($address);
                break;
        }

        if ($result == false) {
            return response()->json([
                'success' => false,
                'error' => 'Withdraw address is invalid'
            ]);
        }

        $fee = $coinInfo && $coinInfo->withdraw_fee ? $coinInfo->withdraw_fee : 0;

        if ($amount - $fee < $coinInfo->min_withdraw) {
            return response()->json([
                'success' => false,
                'error' => 'Withdraw amount must be greater than minimal amount'
            ]);
        }

        $balance = $this->balance($user, $coin);

        if ($balance < $amount) {
            return response()->json([
                'success' => false,
                'error' => 'Your balance is not enough to withdraw'
            ]);
        }

        $tran = new Transaction();
        $tran->user_id = $user->id;
        $tran->datetime = Carbon::now();
        $tran->src_coin = $coin;
        $tran->src_amount = $amount;
        $tran->fee = $fee;
        $tran->type = TransactionType::WITHDRAW;
        $tran->status = TransactionStatus::CONFIRMING_EMAIL;
        $tran->address = $address;
        if ($coin == 'XIN') {
            $tran->publicKey = $publicKey;
        }
        if ($coin == 'XRP') {
            $tran->destination_tag = $destination_tag;
        }
        if ($coin == 'CVN' || $coin == 'DEEX' || $coin == 'BTS' || $coin == 'SCR' || $coin == 'BRAVO') {
            $tran->destination_tag = $memo;
        }
        if ($coin == 'ETN' || $coin == 'CCX') {
            $tran->payment_id = $paymentId;
        }
        if ($coin == 'MPG') {
            $tran->destination_tag = $msg;

            $rpcUrl = env('MPG_RPC_URL');

            $utility = new Utility();
            $url = $rpcUrl.'requestType=getAccountPublicKey&account='.$tran->address;
            $result = $utility->curl_get_contents($url);

            $publicKey = '';
            $address = '';
            if (isset($result->publicKey)) {
                $address = $tran->address;
                $publicKey = $result->publicKey;
            } else if (isset($result->errorDescription)) {
                $url = $rpcUrl.'requestType=getAccountId&publicKey='.$tran->address;
                $result = $utility->curl_get_contents($url);

                if (isset($result->publicKey)) {
                    $address = $result->accountRS;
                    $publicKey = $result->publicKey;
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Invalid account'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Please input public key'
                ]);
            }
            $tran->address = $address;
            $tran->publicKey = $publicKey;
        }
        $tran->save();

        $full_url = $request->fullUrl();
        $url = $request->url();
        $ip = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $request->ip();
        \Log::info('Request Withdraw:: User Id: '.$user->id.', Coin : '.$coin.', Date: '.Carbon::now().', Full Url: '.$full_url.', Url: '.$url.', IP: '.$ip);

        $utility = new Utility();
        $confirm_code = $utility->random(60);

        $confirmWithdraw = new ConfirmWithdraw();
        $confirmWithdraw->user_id = $user->id;
        $confirmWithdraw->confirm_code = $confirm_code;
        $confirmWithdraw->txid = $tran->id;
        $confirmWithdraw->save();

        event(new SendEmailEvent($user, new ConfirmWithdrawMail($user, $confirm_code, $coin, round($amount - $fee, 8), $address)));

        return response()->json([
            'success' => true,
        ]);
    }

    public function confirmWithdraw($code, Request $request) {
        $confirmWithdraw = ConfirmWithdraw::where('confirm_code', $code)->first();

        if (!$confirmWithdraw) {
            var_dump('Your confirm code is wrong!');
            return;
        } else {
            $tran = Transaction::find($confirmWithdraw->txid);
            if ($tran->status != TransactionStatus::CONFIRMING_EMAIL) {
                var_dump('Your confirm code is wrong!');
                return;
            }
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            $confirmWithdraw->delete();

            return redirect(env('CLIENT_URL').'/wallet/withdraw');
        }
    }

    public function cancelWithdraw($coin, Request $request) {
        $user = Auth::user();
        $id = $request->input('id');

        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction does not exist.'
            ]);
        }


        if ($transaction->user_id != $user->id || $transaction->status != TransactionStatus::CONFIRMING_EMAIL || $transaction->src_coin != $coin) {
            return response()->json([
                'success' => false,
                'error' => 'You can not cancel this transaction.'
            ]);
        }

        $confirmWithdraw = ConfirmWithdraw::where('txid', $transaction->id)->first();
        if ($confirmWithdraw) {
            $confirmWithdraw->delete();
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getDepositList($coin, Request $request) {
        $user = Auth::user();
        $data = array();

        $trans = Transaction::where([['user_id', $user->id], ['status', TransactionStatus::SUCCESS], ['type', TransactionType::DEPOSIT], ['dest_coin', $coin]])->orderBy('datetime', 'desc')->get();
        if (count($trans) > 0) {
            foreach ($trans as $record) {
                array_push($data, array(
                    'type' => TransactionType::DEPOSIT,
                    'status' => $record->status,
                    'datetime' => $record->datetime,
                    'txid' => $record->txid,
                    'amount' => $record->dest_amount
                ));
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function getWithdrawList($coin, Request $request) {
        $result = array();

        $user = Auth::user();

        $trans = Transaction::where([['user_id', $user->id], ['type', TransactionType::WITHDRAW], ['src_coin', $coin]])->orderBy('datetime', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function transactionHistory($user, $coin) {
        $data = array();

        $trans = Transaction::where([['user_id', $user->id], ['src_coin', $coin]])
                ->orWhere([['user_id', $user->id], ['dest_coin', $coin]])
                ->orderBy('datetime')
                ->get();
        if (count($trans) > 0) {
            foreach ($trans as $tran) {
                $tran->datetime = strtotime($tran->datetime);
                array_push($data, $tran);
            }
        }

        $trans = Order::where([['user_id', $user->id], ['src_coin', $coin]])->get();
        if (count($trans) > 0) {
            foreach ($trans as $tran) {
                $record = array(
                    'type' => TransactionType::EXCHANGE,
                    'exchange_type' => $tran->type,
                    'src_amount' => $tran->src_amount,
                    'datetime' => strtotime($tran->created_at),
                    'src_coin' => $coin,
                    'dest_coin' => $tran->dest_coin,
                    'rate' => $tran->rate,
                    'open' => 1
                );

                array_push($data, $record);
            }
        }

        return $data;
    }

    public function getTransactionHistory($coin, Request $request) {
        $user = Auth::user();

        $data = $this->transactionHistory($user, $coin);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }


    public function getTotalBalance($coin, Request $request) {
        $walletController = new WalletController();

        $result = 0;

        $coinInfo = Coin::where('coin', $coin)->first();
        if ($coinInfo->is_erc20 == 1) {
            $result = $walletController->getErc20TotalBalance($coin);
            return response()->json([
                'success' => true,
                'balance' => $result
            ]);
        }

        switch ($coin) {
            case 'BTC':
                $result = $walletController->getBTCTotalBalance();
                break;
            case 'ETH':
                $result = $walletController->getETHTotalBalance();
                break;
            case 'XRP':
                $result = $walletController->getXRPTotalBalance();
                break;
            case 'LTC':
                $result = $walletController->getLTCTotalBalance();
                break;
            case 'DOGE':
                $result = $walletController->getDOGETotalBalance();
                break;
            case 'BCH':
                $result = $walletController->getBCHTotalBalance();
                break;
            case 'BTG':
                $result = $walletController->getBTGTotalBalance();
                break;
            case 'ZEC':
                $result = $walletController->getZECTotalBalance();
                break;
            case 'DGB':
                $result = $walletController->getDGBTotalBalance();
                break;
            case 'XMR':
                $result = $walletController->getXMRTotalBalance();
                break;
            case 'XLM':
                $result = $walletController->getXLMTotalBalance();
                break;
            case 'BEN':
                $result = $walletController->getBENTotalBalance();
                break;
            case 'XIN':
                $result = $walletController->getXINTotalBalance();
                break;
            case 'FRST':
                $result = $walletController->getFRSTTotalBalance();
                break;
            case 'USDT':
                $result = $walletController->getUSDTTotalBalance();
                break;
            case 'GRS':
                $result = $walletController->getGRSTotalBalance();
                break;
            case 'SYS':
                $result = $walletController->getSYSTotalBalance();
                break;
            case 'XSN':
                $result = $walletController->getXSNTotalBalance();
                break;
            case 'BTW':
                $result = $walletController->getBTWTotalBalance();
                break;
            case 'MEDIC':
                $result = $walletController->getMEDICTotalBalance();
                break;
            case 'BTCP':
                $result = $walletController->getBTCPTotalBalance();
                break;
            case 'PIRL':
                $result = $walletController->getPIRLTotalBalance();
                break;
            case 'TPAY':
                $result = $walletController->getTPAYTotalBalance();
                break;
            case 'BTS':
                $result = $walletController->getBTSTotalBalance();
                break;
            case 'CVN':
                $result = $walletController->getCVNTotalBalance();
                break;
            case 'DEEX':
                $result = $walletController->getDEEXTotalBalance();
                break;
            case 'HTML':
                $result = $walletController->getHTMLTotalBalance();
                break;
            case 'XVG':
                $result = $walletController->getXVGTotalBalance();
                break;
            case 'ACT':
                $result = $walletController->getACTTotalBalance();
                break;
            case 'VEX':
                $result = $walletController->getVEXTotalBalance();
                break;
            case 'XGOX':
                $result = $walletController->getXGOXTotalBalance();
                break;
            case 'PAC':
                $result = $walletController->getPACTotalBalance();
                break;
            case 'MRI':
                $result = $walletController->getMRITotalBalance();
                break;
            case 'DASH':
                $result = $walletController->getDASHTotalBalance();
                break;
            case 'TRX':
                $result = $walletController->getTRXTotalBalance();
                break;
            case 'IGG':
                $result = $walletController->getTRC20TotalBalance($coin);
                break;
            case 'BTT':
                $result = $walletController->getTRC10TotalBalance($coin);
                break;
            case 'CBC':
                $result = $walletController->getCBCTotalBalance();
                break;
            case 'XZC':
                $result = $walletController->getXZCTotalBalance();
                break;
            case 'NTY':
                $result = $walletController->getNTYTotalBalance();
                break;
            case 'ZEN':
                $result = $walletController->getZENTotalBalance();
                break;
            case 'GXX':
                $result = $walletController->getGXXTotalBalance();
                break;
            case 'BZX':
                $result = $walletController->getBZXTotalBalance();
                break;
            case 'OOT':
                $result = $walletController->getOOTTotalBalance();
                break;
            case 'ABBC':
                $result = $walletController->getABBCTotalBalance();
                break;
            case 'WIRE':
                $result = $walletController->getWIRETotalBalance();
                break;
            case 'CLO':
                $result = $walletController->getCLOTotalBalance();
                break;
            case 'WAVES':
                $result = $walletController->getWAVESTotalBalance();
                break;
            case 'CLB':
                $result = $walletController->getCLBTotalBalance();
                break;
            case 'ADMN':
                $result = $walletController->getADMNTotalBalance();
                break;
            case 'LGS':
                $result = $walletController->getLGSTotalBalance();
                break;
            case 'ETN':
                $result = $walletController->getETNTotalBalance();
                break;
            case 'STK':
                $result = $walletController->getSTKTotalBalance();
                break;
            case 'SCR':
                $result = $walletController->getSCRTotalBalance();
                break;
            case 'XRC':
                $result = $walletController->getXRCTotalBalance();
                break;
            case 'ONT':
                $result = $walletController->getONTTotalBalance();
                break;
            case 'ONG':
                $result = $walletController->getONGTotalBalance();
                break;
            case 'GBX':
                $result = $walletController->getGBXTotalBalance();
                break;
            case 'CCX':
                $result = $walletController->getCCXTotalBalance();
                break;
            case 'TMC':
                $result = $walletController->getTMCTotalBalance();
                break;
            case 'BITG':
                $result = $walletController->getBITGTotalBalance();
                break;
            case 'EXO':
                $result = $walletController->getEXOTotalBalance();
                break;
            case 'BTCONE':
                $result = $walletController->getBTCONETotalBalance();
                break;
            case 'SINS':
                $result = $walletController->getSINSTotalBalance();
                break;
            case 'BRAVO':
                $result = $walletController->getBRAVOTotalBalance();
                break;
            case 'MPG':
                $result = $walletController->getMPGTotalBalance();
                break;
            case 'BTK':
                $result = $walletController->getBTKTotalBalance();
                break;
            case 'VIC':
                $result = $walletController->getVICTotalBalance();
                break;
            case 'TGN':
                $result = $walletController->getTGNTotalBalance();
                break;
            case 'QC':
                $result = $walletController->getQCTotalBalance();
                break;
            case 'BCZ':
                $result = $walletController->getBCZTotalBalance();
                break;
            case 'CMM':
                $result = $walletController->getCMMTotalBalance();
                break;
            case 'CDZC':
                $result = $walletController->getCDZCTotalBalance();
                break;
        }

        return response()->json([
            'success' => true,
            'balance' => $result
        ]);
    }


    public function estimateFee($coin, Request $request) {
        $withdrawController = new WithdrawController();
        $input = $request->input();

        switch ($coin) {
            case 'BTC':
                return $withdrawController->estimateBTCFee($input);
                break;
            case 'LTC':
                return $withdrawController->estimateLTCFee($input);
                break;
            case 'BCH':
                return $withdrawController->estimateBCHFee($input);
                break;
            case 'BTG':
                return $withdrawController->estimateBTGFee($input);
                break;
            case 'ZEC':
                return $withdrawController->estimateZECFee($input);
                break;
            case 'DGB':
                return $withdrawController->estimateDGBFee($input);
                break;
            case 'GRS':
                return $withdrawController->estimateGRSFee($input);
                break;
            case 'SYS':
                return $withdrawController->estimateSYSFee($input);
                break;
            case 'XSN':
                return $withdrawController->estimateXSNFee($input);
                break;
            case 'MEDIC':
                return $withdrawController->estimateMEDICFee($input);
                break;
            case 'BTCP':
                return $withdrawController->estimateBTCPFee($input);
                break;
        }

        return response()->json([
            'success' => false,
            'error' => 'Please try again later.'
        ]);
    }

    public function withdrawCoin($coin, Request $request) {
        $withdrawController = new WithdrawController();
        $input = $request->input();

        $user = Auth::user();

        $coinInfo = Coin::where('coin', $coin)->first();
        if ($coinInfo->is_erc20 == 1) {
            return $withdrawController->withdrawErc20($coin, $input);
        }

        switch ($coin) {
            case 'BTC':
                return $withdrawController->withdrawBTC($input);
                break;
            case 'ETH':
                return $withdrawController->withdrawETH($input);
                break;
            case 'XRP':
                return $withdrawController->withdrawXRP($input);
                break;
            case 'LTC':
                return $withdrawController->withdrawLTC($input);
                break;
            case 'DOGE':
                return $withdrawController->withdrawDOGE($input);
                break;
            case 'BCH':
                return $withdrawController->withdrawBCH($input);
                break;
            case 'BTG':
                return $withdrawController->withdrawBTG($input);
                break;
            case 'ZEC':
                return $withdrawController->withdrawZEC($input);
                break;
            case 'DGB':
                return $withdrawController->withdrawDGB($input);
                break;
            case 'XMR':
                return $withdrawController->withdrawXMR($input);
                break;
            case 'XLM':
                return $withdrawController->withdrawXLM($input);
                break;
            case 'BEN':
                return $withdrawController->withdrawBEN($input);
                break;
            case 'XIN':
                return $withdrawController->withdrawXIN($input);
                break;
            case 'FRST':
                return $withdrawController->withdrawFRST($input);
                break;
            case 'USDT':
                return $withdrawController->withdrawUSDT($input);
                break;
            case 'GRS':
                return $withdrawController->withdrawGRS($input);
                break;
            case 'SYS':
                return $withdrawController->withdrawSYS($input);
                break;
            case 'XSN':
                return $withdrawController->withdrawXSN($input);
                break;
            case 'BTW':
                return $withdrawController->withdrawBTW($input);
                break;
            case 'MEDIC':
                return $withdrawController->withdrawMEDIC($input);
                break;
            case 'BTCP':
                return $withdrawController->withdrawBTCP($input);
                break;
            case 'PIRL':
                return $withdrawController->withdrawPIRL($input);
                break;
            case 'TPAY':
                return $withdrawController->withdrawTPAY($input);
                break;
            case 'BTS':
                return $withdrawController->withdrawBTS($input);
                break;
            case 'CVN':
                return $withdrawController->withdrawCVN($input);
                break;
            case 'DEEX':
                return $withdrawController->withdrawDEEX($input);
                break;
            case 'HTML':
                return $withdrawController->withdrawHTML($input);
                break;
            case 'XVG':
                return $withdrawController->withdrawXVG($input);
                break;
            case 'ACT':
                return $withdrawController->withdrawACT($input);
                break;
            case 'VEX':
                return $withdrawController->withdrawVEX($input);
                break;
            case 'XGOX':
                return $withdrawController->withdrawXGOX($input);
                break;
            case 'PAC':
                return $withdrawController->withdrawPAC($input);
                break;
            case 'MRI':
                return $withdrawController->withdrawMRI($input);
                break;
            case 'DASH':
                return $withdrawController->withdrawDASH($input);
                break;
            case 'TRX':
                return $withdrawController->withdrawTRX($input);
                break;
            case 'IGG':
                return $withdrawController->withdrawTRC20($coin, $input);
                break;
            case 'BTT':
                return $withdrawController->withdrawTRC10($coin, $input);
                break;
            case 'CBC':
                return $withdrawController->withdrawCBC($input);
                break;
            case 'XZC':
                return $withdrawController->withdrawXZC($input);
                break;
            case 'NTY':
                return $withdrawController->withdrawNTY($input);
                break;
            case 'ZEN':
                return $withdrawController->withdrawZEN($input);
                break;
            case 'GXX':
                return $withdrawController->withdrawGXX($input);
                break;
            case 'BZX':
                return $withdrawController->withdrawBZX($input);
                break;
            case 'OOT':
                return $withdrawController->withdrawOOT($input);
                break;
            case 'ABBC':
                return $withdrawController->withdrawABBC($input);
                break;
            case 'WIRE':
                return $withdrawController->withdrawWIRE($input);
                break;
            case 'CLO':
                return $withdrawController->withdrawCLO($input);
                break;
            case 'WAVES':
                return $withdrawController->withdrawWAVES($input);
                break;
            case 'CLB':
                return $withdrawController->withdrawCLB($input);
                break;
            case 'ADMN':
                return $withdrawController->withdrawADMN($input);
                break;
            case 'LGS':
                return $withdrawController->withdrawLGS($input);
                break;
            case 'ETN':
                return $withdrawController->withdrawETN($input);
                break;
            case 'STK':
                return $withdrawController->withdrawSTK($input);
                break;
            case 'SCR':
                return $withdrawController->withdrawSCR($input);
                break;
            case 'XRC':
                return $withdrawController->withdrawXRC($input);
                break;
            case 'ONT':
                return $withdrawController->withdrawONT($input);
                break;
            case 'ONG':
                return $withdrawController->withdrawONG($input);
                break;
            case 'GBX':
                return $withdrawController->withdrawGBX($input);
                break;
            case 'CCX':
                return $withdrawController->withdrawCCX($input);
                break;
            case 'TMC':
                return $withdrawController->withdrawTMC($input);
                break;
            case 'BITG':
                return $withdrawController->withdrawBITG($input);
                break;
            case 'EXO':
                return $withdrawController->withdrawEXO($input);
                break;
            case 'BTCONE':
                return $withdrawController->withdrawBTCONE($input);
                break;
            case 'SINS':
                return $withdrawController->withdrawSINS($input);
                break;
            case 'BRAVO':
                return $withdrawController->withdrawBRAVO($input);
                break;
            case 'MPG':
                return $withdrawController->withdrawMPG($input);
                break;
            case 'BTK':
                return $withdrawController->withdrawBTK($input);
                break;
            case 'VIC':
                return $withdrawController->withdrawVIC($input);
                break;
            case 'TGN':
                return $withdrawController->withdrawTGN($input);
                break;
            case 'QC':
                return $withdrawController->withdrawQC($input);
                break;
            case 'BCZ':
                return $withdrawController->withdrawBCZ($input);
                break;
            case 'CMM':
                return $withdrawController->withdrawCMM($input);
                break;
            case 'CDZC':
                return $withdrawController->withdrawCDZC($input);
                break;
        }

        return response()->json([
            'success' => false,
            'error' => 'Please try again later.'
        ]);
    }

    public function getFastbuyRate($coin, Request $request) {
        $coinInfo = Coin::where('coin', $coin)->first();
        if (!$coinInfo || !$coinInfo->fastbuy_id) {
            return response()->json([
                'success' => true,
                'rate' => 0
            ]);
        }

        $util = new Utility();
        $url = 'https://api.coinmarketcap.com/v1/ticker/'.$coinInfo->fastbuy_id.'/?convert=TRY';
        $result = $util->curl_get_contents($url);

        if (!$result) {
            return response()->json([
                'success' => false,
                'error' => 'Please try again later.'
            ]);
        }
        $avg_rate = $result[0]->price_try;

        $rate = $avg_rate * (100 + $coinInfo->fastbuy_fee) / 100;

        return response()->json([
            'success' => true,
            'rate' => $rate
        ]);
    }

    public function fastBuy($coin, Request $request) {
        $user = Auth::user();

        $input = $request->input();

        return response()->json([
            'success' => false,
            'error' => 'It is closed. Please try again later. '
        ]);

        $coinInfo = Coin::where('coin', $coin)->first();
        if (!$coinInfo || !$coinInfo->fastbuy_id) {
            return response()->json([
                'success' => false,
                'error' => 'Please try again later.'
            ]);
        }

        $util = new Utility();
        $url = 'https://api.coinmarketcap.com/v1/ticker/'.$coinInfo->fastbuy_id.'/?convert=TRY';
        $result = $util->curl_get_contents($url);

        if (!$result) {
            return response()->json([
                'success' => false,
                'error' => 'Please try again later.'
            ]);
        }
        $avg_rate = $result[0]->price_try;

        $rate = $avg_rate * (100 + $coinInfo->fastbuy_fee) / 100;

        $tl_balance = $this->balance($user, 'TL');

        $amount = $input['amount'];

        if ($rate == 0 || !isset($amount) || $amount <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'You can not buy'
            ]);
        }

        $tl_amount = round($amount * $rate, 8);

        if ($tl_amount > $tl_balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your TL balance is not enough'
            ]);
        }

        $tran = new Transaction();
        $tran->user_id = $user->id;
        $tran->datetime = Carbon::now();
        $tran->src_coin = 'TL';
        $tran->src_amount = $tl_amount;
        $tran->dest_coin = $coin;
        $tran->dest_amount = $amount;
        $tran->rate = $rate;
        $tran->type = TransactionType::FASTBUY;
        $tran->status = TransactionStatus::SUCCESS;
        $tran->save();


        return response()->json([
            'success' => true
        ]);
    }

    public function getFastBuyList($coin, Request $request) {
        $input = $request->input();

        $user = Auth::user();

        $transations = Transaction::where([['user_id', $user->id], ['dest_coin', $coin], ['type', TransactionType::FASTBUY]]);

        if (isset($input['limit_count'])) {
            $transations->limit($input['limit_count']);
        }

        $results = $transations->get();

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    public function getStatistics(Request $request) {
        $input = $request->input();

        $query =    "SELECT coins.coin, IFNULL(deposit.amount, 0) deposit_amount, IFNULL(withdraw.amount, 0) withdraw_amount, ".
                            "IFNULL(withdraw.fee_amount, 0) withdraw_fee, IFNULL(exchange.fee_amount, 0) exchange_fee, IFNULL(withdraw_history.fee_amount, 0) withdraw_fee_amount ".
                    "FROM coins ".
                        "LEFT JOIN (SELECT dest_coin, SUM(dest_amount) amount FROM transactions WHERE type = ".TransactionType::DEPOSIT." GROUP BY dest_coin) deposit ON coins.coin = deposit.dest_coin ".
                        "LEFT JOIN (SELECT src_coin, SUM(src_amount - fee) amount, SUM(fee) fee_amount FROM transactions WHERE type = ".TransactionType::WITHDRAW." GROUP BY src_coin) withdraw ON coins.coin = withdraw.src_coin ".
                        "LEFT JOIN (SELECT dest_coin, SUM(fee) fee_amount FROM transactions WHERE type = ".TransactionType::EXCHANGE." GROUP BY dest_coin) exchange ON coins.coin = exchange.dest_coin ".
                        "LEFT JOIN (SELECT coin, SUM(fee) fee_amount FROM coin_withdraw_history GROUP BY coin) withdraw_history ON coins.coin = withdraw_history.coin ";

        $result = \DB::select($query);

        foreach ($result as $record) {
            $record->profit = $record->withdraw_fee + $record->exchange_fee;
            $record->withdraw_amount = $record->withdraw_amount + $record->withdraw_fee_amount;
            $record->balance = $record->deposit_amount - $record->withdraw_amount;
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function rejectWithdraw(Request $request) {
        $input = $request->input();

        $tran = Transaction::find($input['id']);

        if (!$tran || $tran->type != TransactionType::WITHDRAW) {
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
}
