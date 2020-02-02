<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/email', function() {
    return view('emails.en.verify');
});
//Set Locale
Route::post('locale/{locale}', function ($locale) {
    \Illuminate\Support\Facades\App::setLocale($locale);
    var_dump(\Illuminate\Support\Facades\App::getLocale());
});

Route::post('/register', 'Api\UserController@register');
Route::post('/confirm_password', 'Api\UserController@confirmPassword');
Route::post('/token/logout', 'Api\UserController@logoutToken');

Route::get('/currency_rate', 'Api\MarketController@getCurrencyRate');
// accout verify
Route::get('/account/verify/{confirmation_code}', 'Api\UserController@verifyAccount');

// resend activation link
Route::post('/account/sendActivateEmail', 'Api\UserController@sendActivateEmail');

// send reset password mail
Route::post('/account/sendForgotEmail', 'Api\UserController@sendForgotEmail');

// reset password
Route::post('/reset_password', 'Api\UserController@resetPassword');
// get user
Route::get('/refcode/user', 'Api\UserController@getUserByRefCode');

//user login
Route::post('/user/confirm/2fcode', 'Api\UserController@confirm2FCodeAndLogin');
// g2f code
Route::get('/user/g2fcode/generate', 'Api\UserController@generateNewG2FSecurityCode');
// sms code
Route::post('/user/send/sms', 'Api\UserController@sendSMSCode');
// email code
Route::post('/user/send/email', 'Api\UserController@sendEmailCode');
// set auth
Route::post('/user/set/auth', 'Api\UserController@setAuthMode');

// Market rate
Route::get('/market/price', 'Api\MarketController@getPrice');

// Market Ticker
Route::get('/market/ticker', 'Api\MarketController@getTicker');
// Order Book
Route::get('/order_book', 'Api\MarketController@getOrderBook');
// Trades
Route::get('/trades', 'Api\MarketController@getTrades');

// Help page
Route::get('/help/page', 'Api\UserController@getHelpPage');

// Coins
Route::get('/coins', 'Api\MarketController@getCoins');
Route::get('/market/chart_data/{coin}-{marketCoin}', 'Api\MarketController@getChartData');

// Epay
Route::post('/epay/receive/status', 'Api\EPayController@receiveStatus');
Route::post('/{currency}/epay/success', 'Api\EPayController@receiveSuccess');
Route::post('/{currency}/epay/fail', 'Api\EPayController@receiveFail');

// confirm withdraw
Route::get('/confirm/withdraw/{code}', 'Api\CoinController@confirmWithdraw');
// get Last Price
Route::get('/price/last/{marketCoin}-{coin}', 'Api\MarketController@getLastPrice');

// Notify BTC transaction
Route::get('/btc/notify', 'Api\DepositController@detectBTCDeposit');

// IEO
Route::get('/ieo/coins', 'Api\IEOController@getIEOCoins');
Route::get('/ieo/pairs', 'Api\IEOController@getIEOCoinPairs');
Route::get('/ieo/links', 'Api\IEOController@getIEOCoinLinks');
Route::get('/ieo/bonus', 'Api\IEOController@getIEOBonus');
Route::get('/ieo/sts', 'Api\IEOController@getIEOStatistics');
Route::get('/ieo/history', 'Api\IEOController@getIEOHistory');

// assist trades
Route::post('/assist/trade', 'Api\OrderController@assistTrading');

Route::group(['middleware' => ['jwt.auth']], function()
{
    Route::get('/account', 'Api\AccountController@getInfo');
    Route::post('/account/logout', 'Api\AccountController@logOut');
    Route::get('/account/balance', 'Api\AccountController@getBalance');
    Route::get('/getG2FCode', 'Api\UserController@getG2FCode');
    Route::post('/setG2F', 'Api\UserController@setG2F');
    Route::post('/change_password', 'Api\AccountController@changePassword');
    Route::post('/confirmG2FCode', 'Api\UserController@confirmG2FCode');

    Route::post('/email/pin', 'Api\AccountController@sendForgotPinCode');

    Route::get('/coin/address', 'Api\AccountController@getCoinAddress');

    Route::post('/upload/file', 'Api\UploadController@upload');

    Route::post('/pinCode', 'Api\AccountController@savePinCode');

    // CoinPair
    Route::get('/market/coin_pair', 'Api\MarketController@getCoinPairInfo');
    Route::get('/market/user/coin_pair', 'Api\MarketController@getUserCoinPairInfo');
    // Referral
    Route::get('/account/referral', 'Api\AccountController@getReferral');
    Route::get('/account/referral/commission', 'Api\AccountController@getRefCommission');
    Route::get('/account/referral/commission/history', 'Api\AccountController@getCommissionHistory');

    // Settings
    Route::get('/settings/all', 'Api\SettingsController@all');
    Route::get('/settings', 'Api\SettingsController@getSetttings');

    // Profile
    Route::get('/profile', 'Api\AccountController@getProfile');
    Route::get('/verifydata', 'Api\AccountController@getVerifyData');
    Route::post('/verifydata', 'Api\AccountController@setVerifyData');
    // Route::post('/profile', 'Api\AccountController@saveProfile');

    // Bank
    Route::get('/bank', 'Api\AccountController@getBank');
    Route::post('/bank', 'Api\AccountController@saveBank');

    // login history
    Route::get('/login_history', 'Api\AccountController@getLoginHistory');

    // Ticket
    Route::get('/ticket', 'Api\TicketController@getTicket');
    Route::get('/ticket/detail', 'Api\TicketController@getDetail');
    Route::post('/ticket', 'Api\TicketController@postTicket');
    Route::post('/ticket/reply', 'Api\TicketController@replyTicket');

    // Security
    Route::get('/security/g2fcode/generate', 'Api\AccountController@generateNewG2FSecurityCode');
    Route::post('/security/g2fcode/confirm', 'Api\AccountController@confirmG2FCode');
    Route::post('/security/smscode/confirm', 'Api\AccountController@confirmSMSCode');
    Route::post('/security/send/sms', 'Api\AccountController@sendSMSCode');
    Route::post('/security/email/confirm', 'Api\AccountController@confirmEmailCode');
    Route::post('/security/send/email', 'Api\AccountController@sendEmailCode');

    // Epay
    Route::post('/epay/receive/info', 'Api\EPayController@deposit');
    // Bank
    Route::get('/bank', 'Api\BankController@get');

    // TL Controller
    Route::post('/{currency}/deposit', 'Api\FinanceController@deposit')->where('currency', 'TL|USD|EURO');
    Route::post('/{currency}/withdraw', 'Api\FinanceController@withdraw')->where('currency', 'TL|USD|EURO');;
    Route::get('/{currency}/transaction', 'Api\FinanceController@getTransactionList')->where('currency', 'TL|USD|EURO');;
    Route::post('/transaction/cancel', 'Api\FinanceController@cancelTransaction')->where('currency', 'TL|USD|EURO');;

    // Coin Controller

    // Wallet
    Route::post('/{coin}/create/wallet', 'Api\CoinController@createWallet');
    Route::post('/{coin}/withdraw/request', 'Api\CoinController@requestWithdraw');
    Route::post('/{coin}/withdraw/cancel', 'Api\CoinController@cancelWithdraw');
    Route::get('/{coin}/balance', 'Api\CoinController@getBalance');

    // get Transaction History
    Route::get('/{coin}/tx/history', 'Api\CoinController@getTransactionHistory');

    // get Deposit History
    Route::get('/{coin}/deposit/history', 'Api\CoinController@getDepositList');
    // get Withdraw History
    Route::get('/{coin}/withdraw/history', 'Api\CoinController@getWithdrawList');

    // Orders
    Route::get('/order/last/{coin}-{marketCoin}', 'Api\OrderController@getLastOrders');
    Route::post('/order/buy/{coin}-{marketCoin}', 'Api\OrderController@buyOrder');
    Route::post('/order/sell/{coin}-{marketCoin}', 'Api\OrderController@sellOrder');
    Route::post('/order/delete', 'Api\OrderController@deleteOrder');

    Route::get('/order/buy/{coin}-{marketCoin}', 'Api\OrderController@getBuyOrder');
    Route::get('/order/sell/{coin}-{marketCoin}', 'Api\OrderController@getSellOrder');

    Route::get('/order/pending/{coin}-{marketCoin}', 'Api\OrderController@getPendingOrder');
    Route::get('/order/past/{coin}-{marketCoin}', 'Api\OrderController@getPastOrder');

    Route::get('/order/pending/all', 'Api\OrderController@getUserAllPendingOrder');
    Route::get('/order/past/all', 'Api\OrderController@getUserAllPastOrder');
    // Fast Buy
    Route::get('/{coin}/fastbuy/list', 'Api\CoinController@getFastBuyList');
    Route::get('/{coin}/fastbuy/rate', 'Api\CoinController@getFastbuyRate');
    Route::post('/{coin}/fastbuy', 'Api\CoinController@fastBuy');

    Route::get('/XIN/publicKey', 'Api\WalletController@getXINPublicKey');
    Route::get('/XRP/main_wallet', 'Api\WalletController@getXRPMainWallet');

    // ICO
    Route::get('ico/ftc/setting', 'Api\ICOController@getFTCSetting');
    Route::get('ico/ftc/sts', 'Api\ICOController@getFTCStatistics');
    Route::post('ico/ftc/buy', 'Api\ICOController@buyFTC');

    Route::get('ico/stk/setting', 'Api\ICOController@getSTKSetting');
    Route::get('ico/stk/sts', 'Api\ICOController@getSTKStatistics');
    Route::post('ico/stk/buy', 'Api\ICOController@buySTK');

    Route::get('ico/mpay/setting', 'Api\ICOController@getMPAYSetting');
    Route::get('ico/mpay/sts', 'Api\ICOController@getMPAYStatistics');
    Route::post('ico/mpay/buy', 'Api\ICOController@buyMPAY');

    Route::get('ico/laar/setting', 'Api\ICOController@getLAARSetting');
    Route::get('ico/laar/sts', 'Api\ICOController@getLAARStatistics');
    Route::post('ico/laar/buy', 'Api\ICOController@buyLAAR');
});