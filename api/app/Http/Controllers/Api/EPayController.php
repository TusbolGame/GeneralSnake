<?php

namespace App\Http\Controllers\Api;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\EpayPending;
use App\Entities\Transaction;
use App\Libs\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class EPayController extends Controller
{
    const API_KEY = 'ac0c5aeda3c7d3fa70f2c036e49d610d';
    const PAYER_ACCOUNT = 'admin@sistemkoin.com';
    const RECEIVE_URL = 'https://www.epay.com/paymentApi/merReceive';
    const PAYMENT_URL = 'https://www.epay.com/paymentApi/merPayment';

    public function payment($account, $amount, $currency, $txid) {
        $util = new Utility();

        if ($currency == 'EURO') {
            $currency = 'EUR';
        }

        $params = array();
        $params['PAYER_ACCOUNT'] = $this::PAYER_ACCOUNT;
        $params['PAYMENT_AMOUNT'] = $amount;
        $params['PAYMENT_UNITS'] = $currency;
        $params['PAYMENT_ID'] = $txid;
        $params['MEMO'] = '';
        $params['FORCED_PAYEE_ACCOUNT'] = $account;
        $params['INTERFACE_LANGUAGE'] = 'EN_US';
        $params['CHARACTER_ENCODING'] = 'UTF-8';

        $sign = $params['PAYER_ACCOUNT'].":".$params['PAYMENT_AMOUNT'].":".$params['PAYMENT_UNITS'].":".$params['FORCED_PAYEE_ACCOUNT'].":".$this::API_KEY;

        $params['V2_HASH'] = md5($sign);
        $result = $util->curl_post($this::PAYMENT_URL, $params, false);

        return $result;
    }

    public function deposit(Request $request) {
        $input = $request->input();

        if (!isset($input['amount']) || $input['amount'] < 100) {
            return response()->json([
                'success' => false,
                'error' => 'Please input amount greater than 100'
            ]);
        }
        $user = Auth::user();

        $paymentID = Carbon::now()->getTimestamp().rand()*1000000000;
        $paymentAmount = $input['amount'];
        $paymentUnit = $input['currency'];

        $sign = $this::PAYER_ACCOUNT.":".$paymentAmount.":".$paymentUnit.":".$this::API_KEY;
        $v2Hash = md5($sign);

        $epay = new EpayPending();
        $epay->user_id = $user->id;
        $epay->payment_id = $paymentID;
        $epay->status = 0;
        $epay->datetime = Carbon::now();
        $epay->currency = $paymentUnit;
        $epay->save();

        return response()->json([
            'success' => true,
            'paymentID' => $paymentID,
            'v2Hash' => $v2Hash
        ]);
    }

    public function receiveStatus(Request $request) {
        $input = $request->input();

        if ($input['STATUS'] == 2) {
            $epay = EpayPending::where('payment_id', $input['PAYMENT_ID'])->first();

            if (!$epay) {
                return response()->json([
                    'success' => false
                ]);
            }

            $currency = $input['PAYMENT_UNITS'];
            if ($currency == 'EUR') {
                $currency = 'EURO';
            }
            $tran = new Transaction();

            $tran->user_id = $epay->user_id;
            $tran->datetime = Carbon::now();
            $tran->dest_coin = $currency;
            $tran->dest_amount = round($input['PAYMENT_AMOUNT'], 8);
            $tran->type = TransactionType::DEPOSIT;
            $tran->status = TransactionStatus::SUCCESS;
            $tran->from_address = $input['PAYER_ACCOUNT'];
            $tran->to_address = $input['PAYEE_ACCOUNT'];
            $tran->txid = $input['PAYMENT_ID'];
            $tran->epay_order = $input['ORDER_NUM'];

            $tran->save();

            $epay->delete();

            \Log::info('Epay success: '.$input['ORDER_NUM']);
        } else {
            $epay = EpayPending::where('payment_id', $input['PAYMENT_ID']);
            if ($epay) {
                $epay->delete();
            }
        }
    }

    public function receiveSuccess($currency, Request $request) {
        return redirect(env('CLIENT_URL').'/'.$currency.'/epay/success');
    }

    public function receiveFail($currency, Request $request) {
        return redirect(env('CLIENT_URL').'/'.$currency.'/epay/fail');
    }
}
