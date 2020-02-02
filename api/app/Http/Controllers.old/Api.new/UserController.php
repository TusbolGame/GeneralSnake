<?php

namespace App\Http\Controllers\Api;

use App\Entities\LoginHistory;
use App\Entities\Profile;
use App\Entities\ResetPassword;
use App\Entities\SMSCode;
use App\Events\SendEmailEvent;
use App\Libs\GoogleAuthenticator;
use App\Libs\Utility;
use App\Mail\ForgotPasswordMail;
use App\Mail\SMSMail;
use App\Mail\VerifyAccountMail;
use App\User;
use Carbon\Carbon;
use Hamcrest\Util;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    //
    public function register(Request $request) {
        $input = $request->input();

        App::setLocale($input['locale']);

        if (User::where('email', $input['email'])->count() > 0) {
            return response()->json(['success' => false, 'error' => 'Email exist']);
        }

//        if (User::where('name', $input['name'])->count() > 0) {
//            return response()->json(['success' => false, 'error' => 'Username exist']);
//        }

//        if (!isset($input['phone_number']) || $input['phone_number'] == null || $input['phone_number'] == '') {
//            return response()->json(['success' => false, 'error' => 'Please input phone number...']);
//        }

        $utility = new Utility();
        $user = new User();

        $confirmation_code = str_random(30);

        $user->name = $input['name']." ".$input['surname'];
        $user->email = $input['email'];
        $user->password = bcrypt($input['password']);
        $user->confirmed = 0;
        $user->confirmation_code = $confirmation_code;
        if (isset($input['ref_code'])) {
            $referer = User::where('ref_code', $input['ref_code'])->first();
            if ($referer) {
                $user->ref_id = $referer->id;
            }
        }
        $user->role = 'user';
        while (true) {
            $code = $utility->random(10, 1);
            if (!User::where('ref_code', $code)->first()) {
                $user->ref_code = $utility->random(10, 1);
                break;
            }
        }
        $user->save();

        $profile = new Profile();
        $profile->user_id = $user->id;
        $profile->firstname = $input['name'];
        $profile->surname = $input['surname'];
        $profile->phone_number = $input['phone_number'];
        $profile->verify_status = 0;
        $profile->allowed_g2f = 0;
        $profile->save();

        event(new SendEmailEvent($user, new VerifyAccountMail($user)));

        return response()->json(['success' => true]);
    }

    public function confirmPassword(Request $request) {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
        }

        if (env('USER_API') && $user->role == 'superadmin') {
            return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
        } else if (!env('USER_API') && $user->role != 'superadmin') {
            return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
        }

        if ($user->confirmed == 0 || $user->confirmed == null) {
//            return response()->json(['error' => 'Your account is not active. Please go to your email and active account. If not receive, please check in spam box or resend email active.', 'success' => false]);
            return response()->json(['error' => 'Hesabınız aktif değildir. Aktivasyon işlemi için lütfen emailinize yolladığımız linki tıklayıp aktivasyon işlemini gerçekleştirebilirsiniz. Linki göremediyseniz spam kutusunu kontrol ediniz veya yeniden email gönder seçeneğini tıklayınız.', 'success' => false]);
        }

        if ($user->close == 1) {
            return response()->json(['error' => 'Hesabınız kapatılmıştır, Lütfen bizimle iletişime geçiniz', 'success' => false]);
        }

        if (\Hash::check($credentials['password'], $user->password)) {
            $profile = Profile::where('user_id', $user->id)->first();

            $allowed_g2f = 0;
            if ($profile && $profile->allowed_g2f > 0) {
                $allowed_g2f = $profile->allowed_g2f;

                if ($profile->allowed_g2f == 2) {
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
                        $sms_code->validate_time = Carbon::now()->addMinutes(2);
                        $sms_code->save();
                    }
                } else if ($profile->allowed_g2f == 3) {
                    $utility = new Utility();
                    $code = $utility->random(6, 1);

                    $sms_code = SMSCode::where(['email' => $user->email])->first();
                    if (!$sms_code) {
                        $sms_code = new SMSCode();
                    }
                    $sms_code->email = $user->email;
                    $sms_code->code = $code;
                    $sms_code->created_at = date('Y-m-d H:i:s');
                    $sms_code->validate_time = Carbon::now()->addMinutes(2);
                    $sms_code->save();

                    event(new SendEmailEvent($user, new SMSMail($user, $code)));
                }
            }

            return response()->json(['success' => true, 'allowed_g2f' => $allowed_g2f]);
        } else {
            return response()->json(['success' => false, 'error' => 'Password is wrong.']);
        }
    }

    public function login(Request $request) {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
        }

        if (!\Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
        }

        if (env('USER_API') && $user->role == 'superadmin') {
            return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
        } else if (!env('USER_API') && $user->role != 'superadmin') {
            return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
        }

        if ($user->confirmed == 0 || $user->confirmed == null) {
//            return response()->json(['error' => 'Your account is not active. Please go to your email and active account. If not receive, please check in spam box or resend email active.', 'success' => false]);
            return response()->json(['error' => 'Hesabınız aktif değildir. Aktivasyon işlemi için lütfen emailinize yolladığımız linki tıklayıp aktivasyon işlemini gerçekleştirebilirsiniz. Linki göremediyseniz spam kutusunu kontrol ediniz veya yeniden email gönder seçeneğini tıklayınız.', 'success' => false]);
        }

        if ($user->close == 1) {
            return response()->json(['error' => 'Hesabınız kapatılmıştır, Lütfen bizimle iletişime geçiniz', 'success' => false]);
        }

        if ($user->remember_token && $user->remember_token != '') {
            try {
                JWTAuth::setToken($user->remember_token)->invalidate();
            } catch (\Exception $e) {
            }
        }

        try {
            // verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Email or password is wrong', 'success' => false]);
            }
        } catch (JWTException $e) {
            // something went wrong
            return response()->json(['error' => 'token error'], 500);
        }

        $user = Auth::user();
        $user->setRememberToken($token);
        $user->save();

        $data = array();

        $data['success'] = true;
        $data['user_id'] = $user->id;
        $data['email'] = $user->email;
        $data['name'] = $user->name;
        $data['role'] = $user->role;
        $data['ref_code'] = $user->ref_code;
        $data['token'] = $token;

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

        if ($profile) {
            $data['phone_number'] = $profile->phone_number;
        }

        $agent = new Agent();
        $loginHistroy = new LoginHistory();

        $ipaddress = isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $request->ip();

        $loginHistroy->user_id = $user->id;
        $loginHistroy->user_agent = $agent->getUserAgent();
        $loginHistroy->ip_address = isset($ipaddress) ? $ipaddress : '';
        $loginHistroy->ip_location = isset(\Location::get($ipaddress)->countryCode) ? \Location::get($ipaddress)->countryCode : '';
        $loginHistroy->browser = $agent->browser()." ".$agent->version($agent->browser());
        $loginHistroy->device = $agent->platform()." ".$agent->version($agent->platform());
        $loginHistroy->save();

        if ($user->role != 'admin' && $user->role != 'superadmin') {
            $coinController = new CoinController();
            $data['balance'] = $coinController->allBalance($user);
        } else {
        }

        return response()->json($data);
    }

    public function confirm2FCodeAndLogin(Request $request) {
        $input = $request->input();

        $code = $input['code'];
        $email = $input['email'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User Not Found']);
        }

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || $profile->allowed_g2f == 0) {
            return response()->json(['success' => false, 'error' => 'Wrong Code!']);
        }

        if ($profile && $profile->allowed_g2f > 0) {
            if ($profile->allowed_g2f == 1) {
                $gAuth = new GoogleAuthenticator();

                if ($gAuth->checkCode($profile->g2f_security_code, $code)) {
                    return $this->login($request);
                }
            } else if ($profile->allowed_g2f == 2 || $profile->allowed_g2f == 3) {
                $sms = SMSCode::where([['email', $email], ['validate_time', '>=', Carbon::now()]])->first();

                if ($sms && $sms->code == $code) {
                    $sms->delete();

                    return $this->login($request);
                }
            }
        }

        return response()->json(['success' => false, 'error' => 'Wrong Code!']);
    }

    public function verifyAccount($confirmation_code, Request $request) {
        if (!$confirmation_code) {
            return response('Confirmation code is wrong');
        }

        $user = User::where('confirmation_code', $confirmation_code)->first();

        if (!$user) {
            return response('Confirmation code is wrong');
        }

        $user->confirmation_code = '';
        $user->confirmed = 1;

        $user->save();

        return redirect(env('CLIENT_URL').'/login');
    }

    public function sendActivateEmail(Request $request) {
        $input = $request->input();

        App::setLocale($input['locale']);
        $email = $input['email'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Wrong Email']);
        }

        if ($user->confirmed == 1) {
            return response()->json(['success' => false, 'error' => 'Your email was already confirmed.']);
        }

        if (empty($user->confirmation_code)) {
            $confirmation_code = str_random(30);
            $user->confirmation_code = $confirmation_code;
            $user->save();
        }

        event(new SendEmailEvent($user, new VerifyAccountMail($user)));

        return response()->json(['success' => true]);
    }

    public function sendForgotEmail(Request $request) {
        $input = $request->input();

        App::setLocale($input['locale']);
        $email = $input['email'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Wrong Email']);
        }

        $resetPassword = ResetPassword::where('user_id', $user->id)->first();
        if (!$resetPassword) {
            $resetPassword = new ResetPassword();
            $resetPassword->user_id = $user->id;
        }

        $resetPassword->confirm_code = str_random(70);
        $resetPassword->validate_time = Carbon::now()->addMinutes(15);

        $resetPassword->save();

        event(new SendEmailEvent($user, new ForgotPasswordMail($user, $resetPassword->confirm_code)));

        return response()->json(['success' => true]);
    }

    public function resetPassword(Request $request) {
        $input = $request->input();

        $code = $input['confirm_code'];

        if (!$code) {
            return response()->json(['success' => false]);
        }

        $resetPassword = ResetPassword::where([['confirm_code', $code], ['validate_time', '>=', Carbon::now()]])->first();

        if (!$resetPassword) {
            return response()->json(['success' => false, 'error' => 'Wrong Code']);
        }

        $user_id = $resetPassword->user_id;

        $user = User::find($user_id);
        if(!$user) {
            return response()->json(['success' => false, 'error' => 'User Not Exist.']);
        }

        $user->password = bcrypt($input['password']);
        $user->save();

        return response()->json(['success' => true]);
    }

    public function getUserByRefCode(Request $request) {
        $code = $request->input('ref_code');

        $user = User::where('ref_code', $code)->first();
        if (!$user) {
            return response()->json(['success' => false]);
        }

        $data = array(
            'name' => $user->name
        );

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function generateNewG2FSecurityCode(Request $request) {
        $email = $request->input('email');

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User Not Found'
            ]);
        }

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || $profile->allowed_g2f > 0) {
            return response()->json([
                'success' => false,
                'error' => 'You have already set authentication mode'
            ]);
        }

        $gAuth = new GoogleAuthenticator();
        $code = $gAuth->generateSecret();

        $profile->g2f_security_code = $code;
        $profile->save();

        return response()->json([
            'success' => true,
            'code' => $code
        ]);
    }

    public function sendSMSCode(Request $request) {
        $input = $request->input();

        $email = $input['email'];
        // $phone_number = $input['phone_number'];

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User Not Found'
            ]);
        }
        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || $profile->allowed_g2f > 0) {
            return response()->json([
                'success' => false,
                'error' => 'You have already set authentication mode'
            ]);
        }

        $utility = new Utility();
        $code = $utility->random(6, 1);

        $sms = new \App\Libs\SMS;
        if ($sms->send($profile->phone_number, $code)) {
            $sms_code = SMSCode::where(['email' => $email])->first();
            if (!$sms_code) {
                $sms_code = new SMSCode();
            }
            $sms_code->email = $email;
            $sms_code->code = $code;
            $sms_code->created_at =  date('Y-m-d H:i:s');
            $sms_code->validate_time = Carbon::now()->addMinutes(2);
            $sms_code->save();

            return response([
                'success' => true
            ]);
        } else {
            return response()->json(['error' => 'SMS failed']);
        }
    }

    public function sendEmailCode(Request $request) {
        $input = $request->input();

        App::setLocale($input['locale']);

        $email = $input['email'];

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'User Not Found'
            ]);
        }

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || $profile->allowed_g2f > 0) {
            return response()->json([
                'success' => false,
                'error' => 'You have already set authentication mode'
            ]);
        }

        $utility = new Utility();
        $code = $utility->random(6, 1);

        $sms_code = SMSCode::where(['email' => $email])->first();
        if (!$sms_code) {
            $sms_code = new SMSCode();
        }
        $sms_code->email = $email;
        $sms_code->code = $code;
        $sms_code->created_at =  date('Y-m-d H:i:s');
        $sms_code->validate_time = Carbon::now()->addMinutes(2);
        $sms_code->save();

        event(new SendEmailEvent($user, new SMSMail($user, $code)));

        return response([
            'success' => true
        ]);
    }

    public function setAuthMode(Request $request) {
        $input = $request->input();

        $code = $input['code'];
        $allowed_g2f = $input['allowed_g2f'];
        $email = $input['email'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'error' => 'User Not Found']);
        }

        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile || $profile->allowed_g2f > 0) {
            return response()->json([
                'success' => false,
                'error' => 'You have already set authentication mode'
            ]);
        }

        if ($allowed_g2f == 1) {
            $gAuth = new GoogleAuthenticator();

            if ($gAuth->checkCode($profile->g2f_security_code, $code)) {
                $profile->allowed_g2f = 1;
                $profile->save();

                return response([
                    'success' => true
                ]);
            }
        } else if ($allowed_g2f == 2 || $allowed_g2f == 3) {
            $sms = SMSCode::where([['email', $email], ['validate_time', '>=', Carbon::now()]])->first();

            if ($sms && $sms->code == $code) {
                $sms->delete();

                $profile->allowed_g2f = $allowed_g2f;
                $profile->save();

                return response([
                    'success' => true
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'error' => 'Wrong Code'
        ]);
    }
    public function logoutToken(Request $request) {
        $token = $request->input('token');

        if ($token && $token != '') {
            try {
                JWTAuth::setToken($token)->invalidate();
            } catch (\Exception $e) {
            }
        }

        return response([
            'success' => true
        ]);
    }
}
