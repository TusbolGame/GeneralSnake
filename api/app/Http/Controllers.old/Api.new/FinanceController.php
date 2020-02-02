<?php

namespace App\Http\Controllers\Api;

use App\Entities\Profile;
use App\Entities\Setting;
use App\Entities\Transaction;
use App\Http\Controllers\Controller;
use App\Libs\Utility;
use Carbon\Carbon;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceController extends Controller
{
    public function deposit($currency, Request $request) {
        $user = Auth::user();

        $input = $request->input();

        $fee = 0;
        $res = Setting::where('name', strtolower($currency).'_deposit_fee')->first();
        if ($res) {
            $fee = $res->value;
        }

        if (!isset($input['from_address']) || $input['from_address'] == '') {
            return response()->json([
                'success' => false,
                'error' => 'Please select deposit address.'
            ]);
        }

        if (!isset($input['to_address']) || $input['to_address'] == '') {
            return response()->json([
                'success' => false,
                'error' => 'Please select deposit address.'
            ]);
        }

        if (!isset($input['amount']) || $input['amount'] == 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter deposit amount.'
            ]);
        }

        if ($input['amount'] < $fee) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter deposit amount greater than 0.'
            ]);
        }

        if ($input['amount'] < 100) {
            return response()->json([
                'success' => false,
                'error' => 'Lütfen 100 TL ve üzeri yatırınız'
            ]);
        }

        $utility = new Utility();
        $code = $utility->randomStringOnlyUppercase(6);

        $tran = new Transaction();

        $tran->user_id = $user->id;
        $tran->datetime = Carbon::now();
        $tran->dest_coin = $currency;
        $tran->dest_amount = round($input['amount'], 8) - $fee;
        $tran->type = TransactionType::DEPOSIT;
        $tran->status = TransactionStatus::PENDING;
        $tran->from_address = $input['from_address'];
        $tran->to_address = $input['to_address'];
        $tran->fee = $fee;
        $tran->txid = $code;

        $tran->save();

        return response()->json([
            'success' => true,
            'data' => $tran
        ]);
    }

    public function withdraw($currency, Request $request) {
        $user = Auth::user();

        $input = $request->input();

        $auth_code = $input['auth_code'];

        $accountController = new AccountController();
        if (!$accountController->confirmAuthCode($user, $auth_code)) {
            return response()->json([
                'success' => false,
                'error' => 'Wrong Code'
            ]);
        }
        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || $profile->verify_status != 3) {
            return response()->json(['error' => 'Please verify your account.', 'success' => false]);
        }

        if (isset($input['isEpay']) && $input['isEpay'] && (!isset($input['epay_account']) || $input['epay_account'] == '')) {
            return response()->json([
                'success' => false,
                'error' => 'Please input epay account.'
            ]);
        }

        if (!isset($input['amount']) || $input['amount'] <= 0) {
            return response()->json([
                'success' => false,
                'error' => 'Please enter withdraw amount.'
            ]);
        }

        if ($input['amount'] < 100) {
            return response()->json([
                'success' => false,
                'error' => 'Lütfen 100 TL ve üzeri yatırınız'
            ]);
        }

        $fee = 0;
        $res = Setting::where('name', strtolower($currency).'_withdraw_fee')->first();
        if ($res) {
            $fee = $res->value;
        }

        $coinController = new CoinController();
        $balance = $coinController->balance($user, $currency);
        
        if ($input['amount'] > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Your balance is not enough to withdraw.'
            ]);
        }

        $tran = new Transaction();

        $tran->user_id = $user->id;
        $tran->datetime = Carbon::now();
        $tran->src_coin = $currency;
        $tran->src_amount = round($input['amount'], 8);
        $tran->type = TransactionType::WITHDRAW;
        $tran->status = TransactionStatus::PENDING;
        if (isset($input['isEpay']) && $input['isEpay']) {
            $tran->is_epay = 1;
            $tran->to_address = $input['epay_account'];
        } else {
            $tran->to_address = $input['to_address'];
        }
        $tran->fee = $fee;

        $tran->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function cancelTransaction(Request $request) {
        $user = Auth::user();

        $input = $request->input();

        $id = $input['id'];

        $transaction = Transaction::find($id);

        if (!$transaction || $transaction->user_id != $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Transaction Not Found'
            ]);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getTransactionList($currency, Request $request) {
        $input = $request->input();

        $user = Auth::user();

        $transations = Transaction::where('user_id', $user->id)
                        ->where(function ($query) use ($currency) {
                            $query->where([['type', TransactionType::DEPOSIT], ['dest_coin', $currency]]);
                            $query->orWhere([['type', TransactionType::WITHDRAW], ['src_coin', $currency]]);
                        });

        if (isset($input['is_pending'])) {
            $transations->where('status', '<>', TransactionStatus::SUCCESS);
        }

        if (isset($input['is_success'])) {
            $transations->where('status', TransactionStatus::SUCCESS);
        }

        if (isset($input['limit_count'])) {
            $transations->limit($input['limit_count']);
        }

        $results = $transations->orderBy('datetime', 'DESC')->get();

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
}
