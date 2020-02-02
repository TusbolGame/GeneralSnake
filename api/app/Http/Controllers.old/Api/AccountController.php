<?php

namespace App\Http\Controllers\Api;

use App\Constants\MarketOrderType;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\CoinAddress;
use App\Entities\Profile;
use App\Entities\SMSCode;
use App\Events\SendEmailEvent;
use App\Libs\GoogleAuthenticator;
use App\Libs\Utility;
use App\Mail\ForgotPinCodeMail;
use App\Mail\SMSMail;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccountController extends Controller
{
    //
    public function getInfo() {
        $user = Auth::user();

        $data['id'] = $user->id;
        $data['email'] = $user->email;
        $data['name'] = $user->name;
        $data['role'] = $user->role;
        $data['ref_code'] = $user->ref_code;
        $data['pin_code'] = $user->pin_code;

        $profile = Profile::where('user_id', $user->id)->first();
        if ($profile && $profile->allowed_g2f > 0) {
            $data['allowed_g2f'] = $profile->allowed_g2f;
        } else {
            $data['allowed_g2f'] = 0;
        }

        if ($profile && $profile->verify_status == 3) {
            $data['account_verified'] = 1;
        } else {
            $data['account_verified'] = 0;
        }

        if ($user->role != 'admin' && $user->role != 'superadmin') {
            $coinController = new CoinController();
            $data['balance'] = $coinController->allBalance($user);
        } else {
        }

        return response()->json($data);
    }

    public function getBalance(Request $request) {
        $user = Auth::user();

        $successType = TransactionStatus::SUCCESS;
        $failType = TransactionStatus::FAIL;
        $query =    "SELECT coins.*, IFNULL(tran_plus.amount, 0) - IFNULL(tran_minus.amount, 0) AS total_amount,  IFNULL(order_minus.amount, 0) order_amount ".
                    "FROM coins ".
                        "LEFT JOIN (SELECT dest_coin AS coin, SUM(dest_amount) amount FROM transactions WHERE user_id = {$user->id} AND status = {$successType} GROUP BY dest_coin) tran_plus ON coins.coin = tran_plus.coin ".
                        "LEFT JOIN (SELECT src_coin AS coin, SUM(src_amount) amount FROM transactions WHERE user_id = {$user->id} AND status <> {$failType} GROUP BY src_coin) tran_minus ON coins.coin = tran_minus.coin ".
                        "LEFT JOIN (SELECT src_coin AS coin, SUM(src_amount) amount FROM orders WHERE user_id = {$user->id} GROUP BY src_coin) order_minus ON coins.coin = order_minus.coin ";
        $result = \DB::select($query);

        foreach ($result as $record) {
            $record->total_amount = round($record->total_amount, 8);
            $record->available_amount = round($record->total_amount - $record->order_amount, 8);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function changePassword(Request $request) {
        $input = $request->input();

        $user = Auth::user();
        if (\Hash::check($input['curPassword'], $user->password)) {
            $user->password = bcrypt($input['password']);
            $user->save();

            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'error' => 'Current password is wrong.']);
        }
    }

    public function getProfile(Request $request) {
        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            $profile = array();
        }
        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }

    public function saveProfile(Request $request) {
        $user = Auth::user();
        $input = $request->input();

        $profile = Profile::where('user_id', $user->id)->first();
        if (!$profile) {
//            $profile = new Profile();
//            $profile->user_id = $user->id;
            return response()->json([
                'success' => false,
                'error' => 'User does not exist'
            ]);
        }

        foreach ($input as $key => $value) {
            if ($key == 'locale') continue;
            if ($key == 'email') continue;
            if ($key == 'verify_status' && $value == 3) continue;
            if ($key == 'user_id') continue;
            if ($key == 'firstname') continue;
            if ($key == 'surname') continue;

            $profile[$key] = $value;
        }

        $profile->save();

        $user->name = $profile->firstname." ".$profile->surname;
        $user->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function getCoinAddress(Request $request) {
        $user = Auth::user();

        $address = CoinAddress::where('user_id', $user->id)->first();

        if (!$address) {
            $address = new CoinAddress();
            $address->user_id = $user->id;
            $address->save();
        }

        return response()->json([
            'success' => true,
            'data' => $address
        ]);
    }

    public function generateNewG2FSecurityCode(Request $request) {
        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || $profile->allowed_g2f == 1) {
            return response()->json([
                'success' => false,
            ]);
        }

        $gAuth = new GoogleAuthenticator();
        $code = $gAuth->generateSecret();

        $profile->g2f_security_code = $code;
        $profile->save();

        return response()->json([
            'success' => true,
            'code' => $profile->g2f_security_code
        ]);
    }

    public function confirmG2FCode(Request $request) {
        $input = $request->input();

        if (!isset($input['code'])) {
            return response()->json([
                'success' => false,
            ]);
        }

        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
            ]);
        }

        $code = $input['code'];

        $gAuth = new GoogleAuthenticator();

        if ($gAuth->checkCode($profile->g2f_security_code, $code)) {
            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
            ]);
        }
    }


    public function sendSMSCode(Request $request) {
        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
            ]);
        }

        $utility = new Utility();
        $code = $utility->random(6, 1);

        $sms = new \App\Libs\SMS;
        if ($sms->send($profile->phone_number, $code)) {
            $sms_code = SMSCode::where(['email' => $user->email])->first();
            if (!$sms_code) {
                $sms_code = new SMSCode();
            }
            $sms_code->email = $user->email;
            $sms_code->code = $code;
            $sms_code->created_at =  date('Y-m-d H:i:s');
            $sms_code->save();

            return response([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => true,
                'error' => 'SMS failed'
            ]);
        }
    }

    public function confirmSMSCode(Request $request) {
        $input = $request->input();

        $user = Auth::user();
        $code = $input['code'];

        $sms = SMSCode::where('email', $user->email)->first();

        if ($sms && $sms->code == $code) {
            $sms->delete();

            return response([
                'success' => true
            ]);
        }

        return response([
            'success' => false
        ]);
    }

    public function sendEmailCode(Request $request) {
        $user = Auth::user();

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json([
                'success' => false,
            ]);
        }

        $utility = new Utility();
        $code = $utility->random(6, 1);

        $sms_code = SMSCode::where(['email' => $user->email])->first();
        if (!$sms_code) {
            $sms_code = new SMSCode();
        }
        $sms_code->email = $user->email;
        $sms_code->code = $code;
        $sms_code->created_at =  date('Y-m-d H:i:s');
        $sms_code->save();

        event(new SendEmailEvent($user, new SMSMail($user, $code)));

        return response([
            'success' => true
        ]);
    }

    public function confirmEmailCode(Request $request) {
        $input = $request->input();

        $user = Auth::user();
        $code = $input['code'];

        $sms = SMSCode::where('email', $user->email)->first();

        if ($sms && $sms->code == $code) {
            $sms->delete();

            return response([
                'success' => true
            ]);
        }

        return response([
            'success' => false
        ]);
    }

    public function getReferral(Request $request)
    {
        $user = Auth::user();

        $refs = User::where('ref_id', $user->id)->get();

        return response()->json([
            'success' => true,
            'data' => $refs
        ]);
    }   
    
    public function getRefCommission(Request $request) {
        $user = Auth::user();

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
        $user = Auth::user();

        $query =    "SELECT dest_coin AS coin, transactions.*, users.email ".
                    "FROM transactions LEFT JOIN users ON users.id = transactions.ref_id ".
                    "WHERE transactions.user_id = {$user->id} AND transactions.type = ".TransactionType::BONUS." ".
                    "ORDER BY transactions.id";

        $result = \DB::select($query);

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    public function savePinCode(Request $request) {
        $user = Auth::user();

        $pinCode = $request->input('pinCode');
        if (!$pinCode) {
            return response()->json([
                'success' => false,
                'error' => 'PinCode Not Exist'
            ]);
        }

        $user->pin_code = $pinCode;
        $user->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function sendForgotPinCode(Request $request) {
        $user = Auth::user();

        if (!$user->pin_code) {
            return response()->json([
                'success' => false,
                'error' => 'PinCode Not Exist'
            ]);
        }
        event(new SendEmailEvent($user, new ForgotPinCodeMail($user)));

        return response()->json(['success' => true]);
    }

    public function logOut(Request $request) {
        $user = Auth::user();

        if ($user->remember_token && $user->remember_token != '') {
            try {
                JWTAuth::setToken($user->remember_token)->invalidate();
            } catch (\Exception $e) {
                return response()->json(['success' => false]);
            }
        }
        return response()->json(['success' => true]);
    }
}
