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

Route::get('/ethereum/scan/test', 'Api\TestController@etherScan');
Route::get('/ethereum/withdraw/test', 'Api\TestController@ethWithdrawTest');
Route::get('/ethereum/balance/all', 'Api\TestController@getAllBalance');
Route::get('/erc20/balance/all', 'Api\TestController@getAllErc20Balance');
Route::get('/achain/scan/test', 'Api\TestController@achainScan');
Route::get('/pirl/scan/test', 'Api\TestController@pirlScan');
Route::get('/clo/scan/test', 'Api\TestController@CLOScan');
Route::get('/waves/scan/test', 'Api\TestController@WAVESScan');
Route::get('/etn/scan/test', 'Api\TestController@ETNScan');
Route::get('/scorum/scan/test', 'Api\TestController@scorumScan');
Route::get('/gxx/scan/test', 'Api\TestController@gxxScan');
Route::get('/ieo/{coin}/open', 'Api\TestController@ieoCoinOpen');
Route::get('/ieo/{coin}/close', 'Api\TestController@ieoCoinClose');

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
Route::post('/ieo/buy', 'Api\IEOController@buyToken');

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
    Route::get('/account/bank', 'Api\AccountController@getAccountBank');
    Route::post('/account/bank', 'Api\AccountController@saveAccountBank');

    // login history
    Route::get('/login_history', 'Api\AccountController@getLoginHistory');

    // Ticket
    Route::get('/ticket', 'Api\TicketController@getTicket');
    Route::get('/ticket/detail', 'Api\TicketController@getDetail');
    Route::post('/ticket', 'Api\TicketController@postTicket');
    Route::post('/ticket/reply', 'Api\TicketController@replyTicket');

    // Security
    Route::get('/security/g2fcode/generate', 'Api\AccountController@generateNewG2FSecurityCode');
    Route::post('/security/send/auth/code', 'Api\AccountController@sendAuthCode');

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

    Route::get('/order/pending/all', 'Api\OrderController@getUserAllPendingOrder');
    Route::get('/order/past/all', 'Api\OrderController@getUserAllPastOrder');

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
    // Admin Api
    Route::group(['middleware' => ['role:admin|superadmin']], function () {
        Route::get('/admin/users', 'Api\AdminController@getUsers');
        Route::get('/admin/users/{coin}', 'Api\AdminController@getCoinUsers');

        Route::get('/admin/user/id/{id}', 'Api\AdminController@getUserById');
        Route::get('/admin/user/email/{email}', 'Api\AdminController@getUserByEmail');
        Route::get('/admin/user/balance', 'Api\AdminController@getUserBalance');

        Route::post('/admin/user/delete', 'Api\AdminController@deleteUser');
        Route::post('/admin/user/confirm', 'Api\AdminController@confirmUser');

        Route::post('/admin/user/close', 'Api\AdminController@closeUser');
        Route::post('/admin/user/open', 'Api\AdminController@openUser');
        // Coin
        Route::post('/admin/coin/update', 'Api\AdminController@updateCoinInfo');
        // Referral
        Route::get('/admin/referral', 'Api\AdminController@getUserReferral');
        Route::get('/admin/referral/commission', 'Api\AdminController@getRefCommission');
        Route::get('/admin/referral/commission/history', 'Api\AdminController@getCommissionHistory');

        // transaction
        Route::get('/admin/user/{coin}/history', 'Api\AdminController@getCoinHistory');

        // Withdraw History
        Route::get('/admin/{coin}/withdraw/history', 'Api\AdminController@getWithdrawHistory');
        Route::get('/admin/{coin}/withdraw/detail', 'Api\AdminController@getWithdrawDetail');

        // Fastbuy History
        Route::get('/admin/{coin}/fastbuy/history', 'Api\AdminController@getFastbuyHistory');

        // Market Orders
        Route::get('/admin/market/orders/{coin}-{marketCoin}', 'Api\OrderController@getOpenAllOrders');

        // Market History
        Route::get('/admin/market/history/{coin}-{marketCoin}', 'Api\MarketController@getMarketHistory');
        Route::post('/admin/markettx/delete', 'Api\MarketController@deleteMarketHistory');

        // Withdraw TL
        Route::post('/admin/{currency}/withdraw/pending', 'Api\AdminController@withdrawFinancePending')->where('currency', 'TL|USD|EURO');;

        // Deposit TL
        Route::get('/admin/{currency}/deposit/pending', 'Api\AdminController@getDepositPending')->where('currency', 'TL|USD|EURO');
        Route::post('/admin/{currency}/deposit', 'Api\AdminController@depositFinancePending')->where('currency', 'TL|USD|EURO');
        Route::post('/admin/{currency}/deposit/reject', 'Api\AdminController@rejectFinanceDeposit')->where('currency', 'TL|USD|EURO');
        Route::post('/admin/{currency}/pending/delete', 'Api\AdminController@deleteFinancePending')->where('currency', 'TL|USD|EURO');

        Route::get('/admin/{coin}/deposit/history', 'Api\AdminController@getDepositHistory');
        // Settings
        Route::post('/admin/settings/add', 'Api\SettingsController@addSetting');
        Route::post('/admin/settings/delete', 'Api\SettingsController@deleteSetting');
        Route::post('/admin/settings/save', 'Api\SettingsController@saveSetting');

        // User Profile
        Route::get('/admin/user/profile', 'Api\AdminController@getUserProfile');
        Route::post('/admin/user/profile/update', 'Api\AdminController@updateUserProfile');
        Route::post('/admin/user/profile/verify', 'Api\AdminController@verifyUserProfile');

        // Setting
        Route::get('/admin/settings', 'Api\AdminController@getSettings');

        // Statistics
        Route::get('/admin/users/statistics', 'Api\AdminController@getUserStatistics');
        Route::get('/admin/coin/statistics', 'Api\CoinController@getStatistics');

        // Ticket
        Route::get('/ticket/all', 'Api\TicketController@getAllTicket');
        Route::post('/ticket/close', 'Api\TicketController@closeTicket');

        // Bank
        Route::post('/bank', 'Api\BankController@save');
        Route::post('/bank/delete', 'Api\BankController@delete');

        // Generate Admin Address
        // Route::post('{coin}/create/adminwallet', 'Api\CoinController@generateAdminAddress');

        // ICO History
        Route::get('/admin/{coin}/ico/history', 'Api\AdminController@getICOHistory');
        Route::get('/admin/{coin}/ico/amount', 'Api\IEOController@getIEOAmountbyCoin');

        // Market Orders
        Route::get('/admin/market/competition/{coin}-{marketCoin}', 'Api\AdminController@getCompetition');
    });

    Route::group(['middleware' => ['role:superadmin']], function () {

        // Withdraw Pending
        Route::get('/admin/{coin}/withdraw/pending', 'Api\AdminController@getWithdrawPending');
        Route::get('/admin/withdraw/count', 'Api\AdminController@getWithdrawCountByCoin');

        // Withdraw
        Route::post('{coin}/withdraw/estimate_fee', 'Api\CoinController@estimateFee');
        Route::get('{coin}/wallet/total_balance', 'Api\CoinController@getTotalBalance');
        Route::post('{coin}/withdraw', 'Api\CoinController@withdrawCoin');
        Route::post('{coin}/withdraw/reject', 'Api\CoinController@rejectWithdraw');

        // Manual
        Route::post('/admin/user/{coin}/addManual', 'Api\AdminController@addManual');
        Route::post('/admin/user/{coin}/deleteManual', 'Api\AdminController@deleteManual');

        // get All Wallet
        Route::get('/admin/{coin}/wallet/all', 'Api\AdminController@getAllWallet');

        Route::post('/admin/{coin}/wallet/move', 'Api\MoveController@moveCoinToMainWallet');

        Route::get('/admin/coin_pair/all', 'Api\AdminController@getAllCoinPairs');
        Route::post('/admin/coin_pair/suspend', 'Api\AdminController@suspendCoinPair');
        Route::post('/admin/coin_pair/unsuspend', 'Api\AdminController@unsuspendCoinPair');

        Route::get('/admin/wallet/statistics', 'Api\AdminController@getWalletStatistics');

        Route::get('/admin/ieo/pair/all', 'Api\AdminController@getAllIeoCoinPairs');
        Route::post('/admin/ieo/pair/suspend', 'Api\AdminController@suspendIeoCoinPair');
        Route::post('/admin/ieo/pair/unsuspend', 'Api\AdminController@unsuspendIeoCoinPair');
    });
});