<?php

namespace App\Http\Controllers\Api;

use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\AchainSetting;
use App\Entities\AdminSetting;
use App\Entities\BitcoenAddress;
use App\Entities\BitwhiteAddress;
use App\Entities\CLOAddress;
use App\Entities\CLOPending;
use App\Entities\CLOSetting;
use App\Entities\CoinAddress;
use App\Entities\ConcealSetting;
use App\Entities\EthereumAddress;
use App\Entities\EthereumMove;
use App\Entities\EthereumPending;
use App\Entities\EthereumSetting;
use App\Entities\MoveHistory;
use App\Entities\MPGSetting;
use App\Entities\NextyAddress;
use App\Entities\ONTAddress;
use App\Entities\ONTSetting;
use App\Entities\PirlAddress;
use App\Entities\RippleSetting;
use App\Entities\TGNSetting;
use App\Entities\Transaction;
use App\Entities\TronAddress;
use App\Entities\XinAddress;
use App\Libs\BitcoenRPC;
use App\Libs\JsonRPCClient;
use App\Http\Controllers\Controller;
use App\Libs\Utility;
use App\User;
use Carbon\Carbon;
use Hamcrest\Util;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    const ETH_UNIT = 1 / 1000000000000000000;
    const ETH_DECIMALS = 18;

    const ERC20_TRANSFER_HEX = '0xa9059cbb';
    const ERC20_BALANCEOF_HEX = '0x70a08231';

    const XRP_UNIT = 1 / 1000000;
    const XMR_UNIT = 1 / 1000000000000;
    const XIN_UNIT = 1 / 100000000;

    const USDT_PROPERTY = 31;

    const BTW_UNIT = 1 / 100000000;
    const BTW_FEE = 0.1;

    const PIRL_UNIT = 1 / 1000000000000000000;
    const NTY_UNIT = 1 / 1000000000000000000;

    const BTS_UNIT = 1 / 100000;
    const BTS_ASSETID = '1.3.0';
    const CVN_UNIT = 1 / 10000;
    const BTS_ACCOUNT_NAME = 'sk-bitshares';
    const BTS_ACCOUNT_ID = '1.2.981431';
    const BTS_WALLET_PWD = 'OIjoiljcvoijsodfjoaisdjfoahwefiuschvihasoi2938r4uoisdfjkasf';
    const CVN_ASSETID = '1.3.4333';
    const DEEX_UNIT = 1 / 10000;
    const DEEX_ASSETID = '1.3.2230';

    const ACT_UNIT = 1 / 100000;
    const VEX_UNIT = 1 / 100000;

    const TRX_UNIT = 1 / 1000000;
    const TRC20_DECIMALS = array(
        'IGG' => 6
    );
    const TRC10_DECIMALS = array(
        'BTT' => 6
    );
    const MPG_UNIT = 1 / 100000000;

    const ERC20_DECIMALS = array(
        'WRC' => 6,
        'WPR' => 18,
        'DRG' => 8,
        'LNC' => 18,
        'MTC' => 18,
        'RLX' => 18,
        'HBZ' => 18,
        'FRV' => 8,
        'BBC' => 18,
        'VIO' => 18,
        'BEZ' => 18,
        'VRS' => 6,
        'XGPC' => 0,
        'NGC' => 18,
        'MWAT' => 18,
        'YLD' => 2,
        'ABYSS' => 18,
        'HKY' => 18,
        'JAAG' => 18,
        'SEO' => 6,
        'XET' => 8,
        'GNT' => 18,
        'VET' => 18,
        'BRZC' => 18,
        'NCC' => 18,
        'XCCZ' => 16,
        'NPXS' => 18,
        'USC' => 18,
        'STQ' => 18,
        'TOL' => 18,
        'SWL' => 8,
        'OMG' => 18,
        'MKR' => 18,
        'ZRX' => 18,
        'BAT' => 18,
        'AE' => 18,
        'ZIL' => 12,
        'KZE' => 18,
        'HERC' => 18,
        'WISH' => 18,
        '1UP' => 18,
        'DAI' => 18,
        'EVY' => 12,
        'AGVC' => 18,
        'LIPS' => 8,
        'HKN' => 8,
        'FORK' => 18,
        'HET' => 18,
        'HOT' => 18,
        'TWS' => 8,
        'MNC' => 18,
        'QUSD' => 6,
        'TRM' => 8,
        'KUE' => 18,
        'OROX' => 18,
        'BWT' => 18,
        'MPAY' => 18,
        'BTK' => 18,
        'DENT' => 8,
        'LAAR' => 18,
        'ANKR' => 18,
        'W12' => 18,
        'CBEX' => 18,
        'OXY2' => 5,
        'QBIT' => 18,
        'ETHPLO' => 6,
        'SRX' => 6,
        'UPC' => 8
    );
    const CLO_UNIT = 1 / 1000000000000000000;
    const CLO_DECIMALS = 18;

    const CLB_UNIT = 1 / 100000000;
    const WAVES_UNIT = 1 / 100000000;
    const CLB_ASSETID = 'CfMBo6GAZZhXs3bbS64NwAor24CuXwYVGJ34SSkPKHVf';
    //
    const ETN_UNIT = 1 / 100;

    const ONG_UNIT = 1 / 1000000000;
    const ONT_UNIT = 1;
    const ONG_CONTRACT_ADDRESS = '0200000000000000000000000000000000000000';
    const ONT_CONTRACT_ADDRESS = '0100000000000000000000000000000000000000';
    
    const CCX_UNIT = 1 / 1000000;

    public function createBTCWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->btc_address)) {
            return $coinAddress->btc_address;
        }

        $rpcUrl = env('BITCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress();

            $coinAddress->btc_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createBTCAdminWallet() {
        $rpcUrl = env('BITCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'btc_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateBTCAddress($address) {
        $rpcUrl = env('BITCOIN_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBTCTotalBalance() {
        $rpcUrl = env('BITCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function checkBTCBalance() {
        $rpcUrl = env('BITCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        if ($total_balance > 0.1) {
            try {
                $result = $jsonRpc->sendtoaddress(env('BITCOIN_COLD_WALLET_ADDRESS'), $total_balance, '', '', true);

                $move = new MoveHistory();
                $move->coin = 'BTC';
                $move->datetime = Carbon::now();
                $move->txid = $result;
                $move->from = '';
                $move->to = env('BITCOIN_COLD_WALLET_ADDRESS');
                $move->amount = $total_balance;
                $move->fee = 0;
                $move->save();
            } catch (\Exception $e) {

            }
        }
    }

    public function checkETHPending() {
        $rpcUrl = env('ETHEREUM_ADMIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $pendings = EthereumPending::all();

        foreach ($pendings as $pending) {
            try {
                $receipt_tx = $jsonRpc->eth_getTransactionReceipt($pending->txid);
            } catch (\Exception $e) {
                continue;
            }

            if ($receipt_tx['status'] == '0x1') {
                $withdraw = Transaction::where([['type', TransactionType::WITHDRAW], ['txid', $pending->txid]])->first();

                if ($withdraw) {
                    $withdraw->status = TransactionStatus::SUCCESS;
                    $withdraw->save();
                }
                $pending->delete();
            } else {
//                $withdraw = Transaction::where([['type', TransactionType::WITHDRAW], ['txid', $pending->txid]])->first();
//
//                if ($withdraw) {
//                    $withdraw->status = TransactionStatus::FAIL;
//                    $withdraw->save();
//                }
//                $pending->delete();
            }
        }
    }

    public function moveETH($from, $amount) {
        $ethSetting = EthereumSetting::pluck('value', 'name');
        $to = $ethSetting['admin_address'];

        if ($from == $to) return;

        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethAddress = EthereumAddress::where('address', $from)->first();

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, $this::ETH_DECIMALS);

        $result = '';
        try {
            $gasPrice = $jsonRpc->eth_gasPrice();

            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x'.$amountHex,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice) * 2)
            ));

            $result = $jsonRpc->personal_sendTransaction(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x'.$amountHex,
                'gas' => $estimateGas,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice) * 2)
            ), $ethAddress->secret);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return;
        }

        $pending = new EthereumPending();
        $pending->from = $from;
        $pending->to = $to;
        $pending->amount = $amount;
        $pending->txid = $result;
        $pending->coin = 'ETH';

        $pending->save();
    }

    public function checkETHAddress($address) {
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethSetting = EthereumSetting::pluck('value', 'name');

        $balance = 0;
        try {
            $balance = $jsonRpc->eth_getBalance($address, 'latest');
            $balance = hexdec($balance) * $this::ETH_UNIT;
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = EthereumPending::where([['from', $address], ['coin', 'ETH']])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);
        if ($balance > $ethSetting['eth_limit']) {
            $this->moveETH($address,$balance - $ethSetting['eth_min']);
        }
    }


    public function moveETHForErc20($coin, $address) {
        $rpcUrl = env('ETHEREUM_ADMIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethAddress = EthereumAddress::where('address', $address)->first();

        $ethSetting = EthereumSetting::pluck('value', 'name');

        $from = $ethSetting['admin_address'];
        $amount = $ethSetting['eth_min'];

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, $this::ETH_DECIMALS);

        $result = '';
        try {
            $gasPrice = $jsonRpc->eth_gasPrice();

            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => $address,
                'value' => '0x'.$amountHex,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice) * 2)
            ));

            $result = $jsonRpc->personal_sendTransaction(array(
                'from' => $from,
                'to' => $address,
                'gas' => $estimateGas,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice) * 2),
                'value' => '0x'.$amountHex
            ), $ethSetting['admin_secret']);
        } catch (\Exception $e) {
            return;
        }

        $pending = new EthereumPending();
        $pending->from = $from;
        $pending->to = $address;
        $pending->amount = $amount;
        $pending->txid = $result;
        $pending->coin = 'ETH';

        $pending->save();

        $pending = new EthereumMove();
        $pending->from = $from;
        $pending->to = $address;
        $pending->amount = $amount;
        $pending->txid = $result;
        $pending->coin = $coin;

        $pending->save();
    }

    public function moveErc20($from, $amount, $coin) {
        $ethSetting = EthereumSetting::pluck('value', 'name');
        $to = $ethSetting['admin_address'];

        if ($from == $to) return false;

        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, $this::ERC20_DECIMALS[$coin]);

        $ethAddress = EthereumAddress::where('address', $from)->first();

        $data = $this::ERC20_TRANSFER_HEX.str_pad(substr($to, 2), 64, "0", STR_PAD_LEFT).str_pad($amountHex, 64, "0", STR_PAD_LEFT);

        $estimateGas = 0;
        $gasPrice = 0;
        try {
            $gasPrice = $jsonRpc->eth_gasPrice();

            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => env($coin.'_CONTRACT_ADDRESS'),
                'value' => '0x0',
                'data' => $data,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice) * 2),
            ));
        } catch (\Exception $e) {
            return false;
        }

        $hexGas = $gasPrice;

        $estimateGas = hexdec($estimateGas);
        $gasPrice = hexdec($gasPrice) * $this::ETH_UNIT;

        $eth_balance = $this->getETHBalance($from);

        if ($eth_balance < $estimateGas * $gasPrice * 2) {
            $this->moveETHForErc20($coin, $from);
            return false;
        }

        $result = '';
        try {
            $result = $jsonRpc->personal_sendTransaction(array(
                'from' => $from,
                'to' => env($coin.'_CONTRACT_ADDRESS'),
                'value' => '0x0',
                'gas' => '0x'.dechex($estimateGas),
                'data' => $data,
                'gasPrice' => '0x'.dechex(hexdec($hexGas) * 2),
            ), $ethAddress->secret);
        } catch (\Exception $e) {
            return false;
        }

        $pending = new EthereumPending();
        $pending->from = $from;
        $pending->to = $to;
        $pending->amount = $amount;
        $pending->txid = $result;
        $pending->coin = $coin;
        $pending->save();

        $pending = new EthereumPending();
        $pending->from = $from;
        $pending->to = $to;
        $pending->amount = $estimateGas * $gasPrice * 2;
        $pending->txid = $result;
        $pending->coin = 'ETH';
        $pending->save();

        return true;
    }

    public function checkErc20Address($coin, $address, $manual = false) {
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethSetting = EthereumSetting::pluck('value', 'name');

        $balance = 0;
        try {
            $balanceHex = $jsonRpc->eth_call(array(
                'to' => env($coin.'_CONTRACT_ADDRESS'),
                'data' => $this::ERC20_BALANCEOF_HEX.str_pad(substr($address, 2), 64, "0", STR_PAD_LEFT)
            ), 'latest');

            $balance = hexdec($balanceHex) / pow(10, $this::ERC20_DECIMALS[$coin]);
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = EthereumPending::where([['from', $address], ['coin', $coin]])->sum('amount');

        $balance -= $pendingBalance;

        $balance = floor($balance * pow(10, 8)) / pow(10, 8);

        if (isset($ethSetting[strtolower($coin).'_limit']) && $balance > intval($ethSetting[strtolower($coin).'_limit'])) {
            return $this->moveErc20($address, $balance, $coin);
        } else if ($manual == true) {
            return $this->moveErc20($address, $balance, $coin);
        }

        return false;
    }

    public function createETHWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->eth_address)) {
            return $coinAddress->eth_address;
        }
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);

        try {
            $address = $jsonRpc->personal_newAccount($secret);

            $coinAddress->eth_address = $address;
            $coinAddress->save();

            $ethAddress = new EthereumAddress();
            $ethAddress->user_id = $user->id;
            $ethAddress->address = $address;
            $ethAddress->secret = $secret;
            $ethAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createETHAdminWallet() {
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);
        
        try {
            $address = $jsonRpc->personal_newAccount($secret);
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'eth_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        $ethAddress = new EthereumAddress();
        $ethAddress->user_id = 1;
        $ethAddress->address = $address;
        $ethAddress->secret = $secret;
        $ethAddress->save();

        return $address;
    }

    public function validateETHAddress($address) {
        $util = new Utility();
        if ($util->isEthereumAddress($address)) {
            return true;
        } else {
            return false;
        }
    }

    public function getETHBalance($address) {
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $balance = 0;
        try {
            $balance = $jsonRpc->eth_getBalance($address, 'latest');
            $balance = hexdec($balance) * $this::ETH_UNIT;
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = EthereumPending::where([['from', $address], ['coin', 'ETH']])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);

        return $balance;
    }

    public function getETHTotalBalance() {
        $rpcUrl = env('ETHEREUM_ADMIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethSetting = EthereumSetting::pluck('value', 'name');

        $balance = 0;
        try {
            $balance = $jsonRpc->eth_getBalance($ethSetting['admin_address'], 'latest');
            $balance = hexdec($balance) * $this::ETH_UNIT;
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = EthereumPending::where([['from', $ethSetting['admin_address']], ['coin', 'ETH']])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);

        return $balance;
    }

    public function getErc20Balance($coin, $address) {
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $balance = 0;
        try {
            $balanceHex = $jsonRpc->eth_call(array(
                'to' => env($coin.'_CONTRACT_ADDRESS'),
                'data' => $this::ERC20_BALANCEOF_HEX.str_pad(substr($address, 2), 64, "0", STR_PAD_LEFT)
            ), 'latest');

            $balance = hexdec($balanceHex) / pow(10, $this::ERC20_DECIMALS[$coin]);
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = EthereumPending::where([['from', $address], ['coin', $coin]])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);

        return $balance;
    }

    public function getErc20TotalBalance($coin) {
        $rpcUrl = env('ETHEREUM_ADMIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethSetting = EthereumSetting::pluck('value', 'name');

        $balance = 0;
        try {
            $balanceHex = $jsonRpc->eth_call(array(
                'to' => env($coin.'_CONTRACT_ADDRESS'),
                'data' => $this::ERC20_BALANCEOF_HEX.str_pad(substr($ethSetting['admin_address'], 2), 64, "0", STR_PAD_LEFT)
            ), 'latest');

            $balance = hexdec($balanceHex) / pow(10, $this::ERC20_DECIMALS[$coin]);
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = EthereumPending::where([['from', $ethSetting['admin_address']], ['coin', $coin]])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);

        return $balance;
    }

    public function getXRPMainWallet(Request $request) {
        $rippleSetting = RippleSetting::all()->pluck('value', 'name');

        if (!isset($rippleSetting['address'])) {
            return response()->json([
                'success' => false,
                'error' => 'Address doest not exist'
            ]);
        }

        return response()->json([
            'success' => true,
            'address' => $rippleSetting['address'],
            'minimum_amount' => $rippleSetting['mimimum_withdraw_amount']
        ]);
    }

    public function createXRPWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->xrp_address)) {
            return $coinAddress->xrp_address;
        }

        while (true) {
            $tag = rand(100000000, 999999999);

            $exist = CoinAddress::where('xrp_address', $tag)->first();

            if (!$exist) {
                $coinAddress->xrp_address = $tag;
                $coinAddress->save();

                return $tag;
            }
        }

        return false;
    }

    public function getXRPTotalBalance() {
        $rpcUrl = env('RIPPLE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setRippleFlag();

        $rippleSetting = RippleSetting::all()->pluck('value', 'name');

        try {
            $result = $jsonRpc->account_info(array(
                'account' => $rippleSetting['address']
            ));
        } catch (\Exception $e) {
            return 0;
        }

        if ($result['status'] == 'success') {
            return $result['account_data']['Balance'] * $this::XRP_UNIT;
        } else {
            return 0;
        }
    }

    public function createLTCWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->ltc_address)) {
            return $coinAddress->ltc_address;
        }

        $rpcUrl = env('LTC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->ltc_address = $address;
            $coinAddress->save();
            
            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createLTCAdminWallet() {
        $rpcUrl = env('LTC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'ltc_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateLTCAddress($address) {
        $rpcUrl = env('LTC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getLTCTotalBalance() {
        $rpcUrl = env('LTC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createDOGEWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->doge_address)) {
            return $coinAddress->doge_address;
        }
        $rpcUrl = env('DOGE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->doge_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createDOGEAdminWallet() {
        $rpcUrl = env('DOGE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'doge_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateDOGEAddress($address) {
        $rpcUrl = env('DOGE_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getDOGETotalBalance() {
        $rpcUrl = env('DOGE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }
    
    public function createBCHWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->bch_address)) {
            return $coinAddress->bch_address;
        }
        $rpcUrl = env('BCH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->bch_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createBCHAdminWallet() {
        $rpcUrl = env('BCH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'bch_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateBCHAddress($address) {
        $rpcUrl = env('BCH_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }
    
    public function getBCHTotalBalance() {
        $rpcUrl = env('BCH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createBTGWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->btg_address)) {
            return $coinAddress->btg_address;
        }
        $rpcUrl = env('BTG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->btg_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createBTGAdminWallet() {
        $rpcUrl = env('BTG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'btg_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateBTGAddress($address) {
        $rpcUrl = env('BTG_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBTGTotalBalance() {
        $rpcUrl = env('BTG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }
    
    public function createZECWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->zec_address)) {
            return $coinAddress->zec_address;
        }
        $rpcUrl = env('ZEC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getnewaddress();

            $coinAddress->zec_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createZECAdminWallet() {
        $rpcUrl = env('ZEC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress();
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'zec_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateZECAddress($address) {
        $rpcUrl = env('ZEC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getZECTotalBalance() {
        $rpcUrl = env('ZEC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createDGBWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->dgb_address)) {
            return $coinAddress->dgb_address;
        }
        $rpcUrl = env('DGB_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->dgb_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createDGBAdminWallet() {
        $rpcUrl = env('DGB_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'dgb_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateDGBAddress($address) {
        $rpcUrl = env('DGB_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getDGBTotalBalance() {
        $rpcUrl = env('DGB_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createXMRWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->xmr_address)) {
            return $coinAddress->xmr_address;
        }

        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $result = $jsonRpc->make_integrated_address(array());

            $coinAddress->xmr_address = $result['integrated_address'];
            $coinAddress->xmr_paymentid = $result['payment_id'];
            $coinAddress->save();

            return $result['integrated_address'];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createXMRAdminWallet() {
        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $result = $jsonRpc->make_integrated_address(array());
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'xmr_address';
        $adminSetting->value = $result['integrated_address'];
        $adminSetting->save();

        return $result['integrated_address'];
    }

    public function validateXMRAddress($address) {
        return true;
    }

    public function getXMRTotalBalance() {
        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        $total_balance = 0;
        try {
            $result = $jsonRpc->getbalance(array());
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        $total_balance = $result['balance'] * $this::XMR_UNIT;
        return $total_balance;
    }

    public function createXLMWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->xlm_address)) {
            return $coinAddress->xlm_address;
        }

        return false;
    }

    public function createXLMAdminWallet()
    {
        return false;
    }

    public function validateXLMAddress($address) {
        return true;
    }

    public function getXLMTotalBalance() {
        return 0;
    }

    public function createBENWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->ben_address)) {
            return $coinAddress->ben_address;
        }
        $rpcUrl = env('BITCOEN_RPC_URL');

        $jsonRpc = new BitcoenRPC($rpcUrl);

        try {
            $result = $jsonRpc->createWallet();
        } catch (\Exception $e) {
            return false;
        }

        if ($result['status'] == 'ok') {
            $coinAddress->ben_address = $result['id'];
            $coinAddress->save();

            $bitcoenAddress = new BitcoenAddress();
            $bitcoenAddress->user_id = $user->id;
            $bitcoenAddress->address = $result['id'];
            $bitcoenAddress->tiny_address = BitcoenRPC::getTinyAddress($result);
            $bitcoenAddress->block = $result['block'];
            $bitcoenAddress->public = $result['keysPair']['public'];
            $bitcoenAddress->private = $result['keysPair']['private'];
            $bitcoenAddress->save();

            return $result['id'];
        } else {
            return false;
        }
    }

    public function validateBENAddress($address) {
        $rpcUrl = env('BITCOEN_RPC_URL');
        $jsonRpc = new BitcoenRPC($rpcUrl);

        try {
            $wallet = $jsonRpc->getWalletInfo($address);
        } catch (\Exception $e) {
            return false;
        }

        if (isset($wallet['id'])) {
            return true;
        } else {
            return false;
        }
    }

    public function getBENTotalBalance() {
        $rpcUrl = env('BITCOEN_RPC_URL');

        $jsonRpc = new BitcoenRPC($rpcUrl);

        $total_balance = 0;

        $adminAddress = AdminSetting::where('name', 'ben_address')->first();

        try {
            $wallet = $jsonRpc->getWalletInfo($adminAddress->value);
            $balance = BitcoenRPC::mil2Ben($wallet['balance']);
            $total_balance += $balance;
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            // var_dump($e->getMessage());
        }

        return $total_balance;
    }

    public function getXINPublicKey(Request $request) {
        $address = $request->input('address');

        $xinAddress = XinAddress::where('address', $address)->first();

        if (!$xinAddress/* || $xinAddress->requirePublic == 0*/) {
            return response()->json([
                'success' => false,
                'error' => ''
            ]);
        }

        return response()->json([
            'success' => true,
            'publicKey' => $xinAddress->publicKey
        ]);
    }

    public function createXINWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->xin_address)) {
            return $coinAddress->xin_address;
        }

        $utility = new Utility();
        $passphrase = $utility->random(50);

        $rpcUrl = env('XIN_RPC_URL');
        $url = $rpcUrl."requestType=getAccountId&secretPhrase=".$passphrase;

        $result = $utility->curl_get_contents($url);

        if (isset($result->accountRS)) {
            $coinAddress->xin_address = $result->accountRS;
            $coinAddress->save();

            $xinAddress = new XinAddress();
            $xinAddress->user_id = $user->id;
            $xinAddress->passphrase = $passphrase;
            $xinAddress->address = $result->accountRS;
            $xinAddress->publicKey = $result->publicKey;
            $xinAddress->account = $result->account;
            $xinAddress->requirePublic = 1;
            $xinAddress->save();

            return array(
                'address' => $result->accountRS,
                'publicKey' => $result->publicKey
            );
        }
        return false;
    }

    public function createXINAdminWallet() {
        $utility = new Utility();
        $passphrase = $utility->random(50);

        $rpcUrl = env('XIN_RPC_URL');
        $url = $rpcUrl."requestType=getAccountId&secretPhrase=".$passphrase;

        $result = $utility->curl_get_contents($url);

        if (isset($result->accountRS)) {
            $adminSetting = new AdminSetting();
            $adminSetting->name = 'xin_address';
            $adminSetting->value = $result->accountRS;
            $adminSetting->save();

            $xinAddress = new XinAddress();
            $xinAddress->user_id = 1;
            $xinAddress->passphrase = $passphrase;
            $xinAddress->address = $result->accountRS;
            $xinAddress->publicKey = $result->publicKey;
            $xinAddress->account = $result->account;
            $xinAddress->requirePublic = 1;
            $xinAddress->save();

            return array(
                'address' => $result->accountRS,
                'publicKey' => $result->publicKey
            );
        }
        return false;
    }

    public function validateXINAddress($address, &$publicKey) {
        $util = new Utility();

        $rpcUrl = env('XIN_RPC_URL');

        if (isset($publicKey) && $publicKey != '') {
            $url = $rpcUrl."requestType=getAccountId&publicKey=".$publicKey;
            $result = $util->curl_get_contents($url);

            if (!isset($result->accountRS)) {
                return false;
            }

            if ($address != $result->accountRS) {
                return false;
            }
        } else {
            $url = $rpcUrl."getAccountPublicKey&account=".$address;
            $result = $util->curl_get_contents($url);

            if (!isset($result->publicKey)) {
                return false;
            }

            $publicKey = $result->publicKey;
        }

        return true;
    }

    public function getXINTotalBalance() {
        $rpcUrl = env('XIN_RPC_URL');

        $utility = new Utility();

        $addresses = XinAddress::all();
        $total_balance = 0;

        foreach ($addresses as $address) {
            $url = $rpcUrl."requestType=getAccount&account=".$address->address;
            $result = $utility->curl_get_contents($url);

            if (isset($result->balanceTQT)) {
                $total_balance += $result->balanceTQT * $this::XIN_UNIT;
            }
        }

        return $total_balance;
    }
    
    public function createFRSTWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->frst_address)) {
            return $coinAddress->frst_address;
        }

        $rpcUrl = env('FIRSTCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->frst_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createFRSTAdminWallet() {
        $rpcUrl = env('FIRSTCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'frst_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateFRSTAddress($address) {
        $rpcUrl = env('FIRSTCOIN_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getFRSTTotalBalance() {
        $rpcUrl = env('FIRSTCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    //
    public function createUSDTWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->usdt_address)) {
            return $coinAddress->usdt_address;
        }

        $rpcUrl = env('OMNICORE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->usdt_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createUSDTAdminWallet() {
        $rpcUrl = env('OMNICORE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'usdt_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateUSDTAddress($address) {
        $rpcUrl = env('OMNICORE_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getUSDTTotalBalance() {
        $rpcUrl = env('OMNICORE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $coinAddress = CoinAddress::whereNotNull('usdt_address')->get();

        $total_balance = 0;

        foreach ($coinAddress as $address) {
            try {
                $result = $jsonRpc->omni_getbalance($address->usdt_address, $this::USDT_PROPERTY);
                $total_balance += $result['balance'];
//                if ($result['balance'] > 0) {
//                    var_dump($address->usdt_address, $result['balance']);
//                }
            } catch (\Exception $e) {

            }
        }

        return $total_balance;
    }


    public function createGRSWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->grs_address)) {
            return $coinAddress->grs_address;
        }

        $rpcUrl = env('GRS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->grs_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createGRSAdminWallet() {
        $rpcUrl = env('GRS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'grs_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateGRSAddress($address) {
        $rpcUrl = env('GRS_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getGRSTotalBalance() {
        $rpcUrl = env('GRS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }


    public function createSYSWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->sys_address)) {
            return $coinAddress->sys_address;
        }

        $rpcUrl = env('SYS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->sys_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createSYSAdminWallet() {
        $rpcUrl = env('SYS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getaccountaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'sys_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateSYSAddress($address) {
        $rpcUrl = env('SYS_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getSYSTotalBalance() {
        $rpcUrl = env('SYS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createXSNWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->xsn_address)) {
            return $coinAddress->xsn_address;
        }

        $rpcUrl = env('XSN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->xsn_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createXSNAdminWallet() {
        $rpcUrl = env('XSN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getaccountaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'xsn_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateXSNAddress($address) {
        $rpcUrl = env('XSN_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getXSNTotalBalance() {
        $rpcUrl = env('XSN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createBTWWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->btw_address)) {
            return $coinAddress->btw_address;
        }

        $secret = bin2hex(random_bytes(36));
        $utility = new Utility();

        $rpcUrl = env('BITWHITE_RPC_URL');

        $url = $rpcUrl."accounts/open/";

        $result = $utility->curl_post($url, array('secret' => $secret));

        if ($result->success) {
            $coinAddress->btw_address = $result->account->address;
            $coinAddress->save();

            $btwAddress = new BitwhiteAddress();
            $btwAddress->user_id = $user->id;
            $btwAddress->secret = $secret;
            $btwAddress->address = $result->account->address;
            $btwAddress->save();

            return $result->account->address;
        } else {
            return false;
        }
    }

    public function createBTWAdminWallet() {
        $secret = bin2hex(random_bytes(36));
        $utility = new Utility();

        $rpcUrl = env('BITWHITE_RPC_URL');

        $url = $rpcUrl."accounts/open/";

        $result = $utility->curl_post($url, array('secret' => $secret));

        if ($result->success) {
            $adminSetting = new AdminSetting();
            $adminSetting->name = 'btw_address';
            $adminSetting->value = $result->account->address;
            $adminSetting->save();

            $btwAddress = new BitwhiteAddress();
            $btwAddress->user_id = 1;
            $btwAddress->secret = $secret;
            $btwAddress->address = $result->account->address;
            $btwAddress->save();

            return $result->account->address;
        } else {
            return false;
        }
    }

    public function validateBTWAddress($address) {
        $rpcUrl = env('BITWHITE_RPC_URL');

        $utility = new Utility();
        $url = $rpcUrl."accounts/getBalance?address=".$address;

        $result = $utility->curl_get_contents($url);

        if ($result->success) {
            return true;
        } else {
            return false;
        }
    }

    public function getBTWTotalBalance() {
        $rpcUrl = env('BITWHITE_RPC_URL');

        $utility = new Utility();
        $url = $rpcUrl."accounts/getBalance?address=";

        $result = BitwhiteAddress::all();
        $total_balance = 0;

        foreach ($result as $address) {
            $result = $utility->curl_get_contents($url.$address->address);

            if ($result && $result->success) {
                $total_balance += $result->balance * $this::BTW_UNIT;
            }
        }

        return $total_balance;
    }

    public function createMEDICWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->medic_address)) {
            return $coinAddress->medic_address;
        }

        $rpcUrl = env('MEDIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->medic_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createMEDICAdminWallet() {
        $rpcUrl = env('MEDIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getaccountaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'medic_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateMEDICAddress($address) {
        $rpcUrl = env('MEDIC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getMEDICTotalBalance() {
        $rpcUrl = env('MEDIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createBTCPWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->btcp_address)) {
            return $coinAddress->btcp_address;
        }
        $rpcUrl = env('BTCP_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getnewaddress();

            $coinAddress->btcp_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createBTCPAdminWallet() {
        $rpcUrl = env('BTCP_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress();
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'btcp_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateBTCPAddress($address) {
        $rpcUrl = env('BTCP_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBTCPTotalBalance() {
        $rpcUrl = env('BTCP_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createPIRLWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->pirl_address)) {
            return $coinAddress->pirl_address;
        }
        $rpcUrl = env('PIRL_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);

        try {
            $address = $jsonRpc->personal_newAccount($secret);

            $coinAddress->pirl_address = $address;
            $coinAddress->save();

            $pirlAddress = new PirlAddress();
            $pirlAddress->user_id = $user->id;
            $pirlAddress->address = $address;
            $pirlAddress->secret = $secret;
            $pirlAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createPIRLAdminWallet() {
        $rpcUrl = env('PIRL_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);

        try {
            $address = $jsonRpc->personal_newAccount($secret);
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'pirl_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        $pirlAddress = new PirlAddress();
        $pirlAddress->user_id = 1;
        $pirlAddress->address = $address;
        $pirlAddress->secret = $secret;
        $pirlAddress->save();

        return $address;
    }

    public function validatePIRLAddress($address) {
        $util = new Utility();
        if ($util->isEthereumAddress($address)) {
            return true;
        } else {
            return false;
        }
    }

    public function getPIRLTotalBalance() {
        $rpcUrl = env('PIRL_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        $adminAddress = AdminSetting::where('name', 'pirl_address')->first();
        try {
            $balance = $jsonRpc->eth_getBalance($adminAddress->value, 'latest');
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $total_balance += hexdec($balance) * $this::PIRL_UNIT;

        return $total_balance;
    }

    public function createNTYWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->nty_address)) {
            return $coinAddress->nty_address;
        }
        $rpcUrl = env('NTY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);

        try {
            $address = $jsonRpc->personal_newAccount($secret);

            $coinAddress->nty_address = $address;
            $coinAddress->save();

            $ntyAddress = new NextyAddress();
            $ntyAddress->user_id = $user->id;
            $ntyAddress->address = $address;
            $ntyAddress->secret = $secret;
            $ntyAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createNTYAdminWallet() {
        $rpcUrl = env('NTY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);

        try {
            $address = $jsonRpc->personal_newAccount($secret);
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'nty_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        $ntyAddress = new NextyAddress();
        $ntyAddress->user_id = 1;
        $ntyAddress->address = $address;
        $ntyAddress->secret = $secret;
        $ntyAddress->save();

        return $address;
    }

    public function validateNTYAddress($address) {
        $util = new Utility();
        if ($util->isEthereumAddress($address)) {
            return true;
        } else {
            return false;
        }
    }

    public function getNTYTotalBalance() {
        $rpcUrl = env('NTY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $result = NextyAddress::all();

        $total_balance = 0;
        foreach ($result as $address) {
            try {
                $balance = $jsonRpc->eth_getBalance($address->address, 'latest');
            } catch (\Exception $e) {
                $balance = 0;
                //return response('Error! Please try again.', 403);
            }
            $total_balance += hexdec($balance) * $this::NTY_UNIT;
        }

        return $total_balance;
    }

    public function createTPAYWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->tpay_address)) {
            return $coinAddress->tpay_address;
        }

        $rpcUrl = env('TPAY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->tpay_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createTPAYAdminWallet() {
        $rpcUrl = env('TPAY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'tpay_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateTPAYAddress($address) {
        $rpcUrl = env('TPAY_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getTPAYTotalBalance() {
        $rpcUrl = env('TPAY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createHTMLWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->html_address)) {
            return $coinAddress->html_address;
        }

        $rpcUrl = env('HTML_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->html_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createHTMLAdminWallet() {
        $rpcUrl = env('HTML_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'html_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateHTMLAddress($address) {
        $rpcUrl = env('HTML_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getHTMLTotalBalance() {
        $rpcUrl = env('HTML_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function getBTSMainWallet(Request $request)
    {
        return response()->json([
            'success' => true,
            'address' => $this::BTS_ACCOUNT_NAME,
        ]);
    }

    public function createBTSWallet($user, $coinAddress)
    {
        if ($coinAddress && !empty($coinAddress->bts_address)) {
            return $coinAddress->bts_address;
        }

        while (true) {
            $tag = rand(100000000, 999999999);

            $exist = CoinAddress::where('bts_address', $tag)->first();

            if (!$exist) {
                $coinAddress->bts_address = $tag;
                $coinAddress->save();

                return $tag;
            }
        }

        return false;
    }

    public function getBTSTotalBalance() {

        $rpcUrl = env('BITSHARES_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->list_account_balances($this::BTS_ACCOUNT_NAME);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return 0;
        }

        $cvn_balance = 0;

        foreach ($result as $rec) {
            if ($rec['asset_id'] == $this::BTS_ASSETID) {
                $cvn_balance = $rec['amount'] * $this::BTS_UNIT;
                break;
            }
        }

        return $cvn_balance;
    }

    public function getCVNTotalBalance() {

        $rpcUrl = env('BITSHARES_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->list_account_balances($this::BTS_ACCOUNT_NAME);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return 0;
        }

        $cvn_balance = 0;

        foreach ($result as $rec) {
            if ($rec['asset_id'] == $this::CVN_ASSETID) {
                $cvn_balance = $rec['amount'] * $this::CVN_UNIT;
                break;
            }
        }

        return $cvn_balance;
    }

    public function getDEEXTotalBalance() {

        $rpcUrl = env('BITSHARES_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->list_account_balances($this::BTS_ACCOUNT_NAME);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return 0;
        }

        $deex_balance = 0;

        foreach ($result as $rec) {
            if ($rec['asset_id'] == $this::DEEX_ASSETID) {
                $deex_balance = $rec['amount'] * $this::DEEX_UNIT;
                break;
            }
        }

        return $deex_balance;
    }

    public function createXVGWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->xvg_address)) {
            return $coinAddress->xvg_address;
        }

        $rpcUrl = env('XVG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->xvg_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createXVGAdminWallet() {
        $rpcUrl = env('XVG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'xvg_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateXVGAddress($address) {
        $rpcUrl = env('XVG_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getXVGTotalBalance() {
        $rpcUrl = env('XVG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }
    //
    public function createACTWallet($coinAddress) {
        if ($coinAddress && !empty($coinAddress->act_address)) {
            return $coinAddress->act_address;
        }

        $util = new Utility();

        $actSettings = AchainSetting::where('name', 'wallet_address')->first();

        while (true) {
            $subAddress = $util->randomHexString(32);

            $address = $actSettings->value.$subAddress;

            $exist = CoinAddress::where('act_address', $address)->first();

            if (!$exist) {
                $coinAddress->act_address = $address;
                $coinAddress->save();

                return $address;
            }
        }
    }

    public function createACTAdminWallet() {
        return false;
    }

    public function validateACTAddress($address) {
        $rpcUrl = env('ACHAIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->wallet_check_address($address);
        } catch (\Exception $e) {
            return false;
        }

        return $validate;
    }

    public function getACTTotalBalance() {
        $rpcUrl = env('ACHAIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;

        $actSettings = AchainSetting::where('name', 'wallet_address')->first();
        $result = array();
        try {
            $result = $jsonRpc->blockchain_list_address_balances($actSettings->value);
        } catch (\Exception $e) {
        }

        foreach ($result as $rec) {
            if ($rec[1]['condition']['asset_id'] == 0) {
                $total_balance += $rec[1]['balance'] * $this::ACT_UNIT;
            }
        }
        return $total_balance;
    }

    public function getVEXTotalBalance() {
        $vexbalance = AchainSetting::where('name', 'vex_balance')->first();
        return $vexbalance->value;
    }

    public function createXGOXWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->xgox_address)) {
            return $coinAddress->xgox_address;
        }

        $rpcUrl = env('XGOX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->xgox_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createXGOXAdminWallet() {
        $rpcUrl = env('XGOX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'xgox_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateXGOXAddress($address) {
        $rpcUrl = env('XGOX_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getXGOXTotalBalance() {
        $rpcUrl = env('XGOX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createPACWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->pac_address)) {
            return $coinAddress->pac_address;
        }

        $rpcUrl = env('PAC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->pac_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createPACAdminWallet() {
        $rpcUrl = env('PAC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'pac_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validatePACAddress($address) {
        $rpcUrl = env('PAC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getPACTotalBalance() {
        $rpcUrl = env('PAC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createMRIWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->mri_address)) {
            return $coinAddress->mri_address;
        }

        $rpcUrl = env('MRI_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->mri_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createMRIAdminWallet() {
        $rpcUrl = env('MRI_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'mri_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateMRIAddress($address) {
        $rpcUrl = env('MRI_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getMRITotalBalance() {
        $rpcUrl = env('MRI_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createDASHWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->dash_address)) {
            return $coinAddress->dash_address;
        }

        $rpcUrl = env('DASH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->dash_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createDASHAdminWallet() {
        $rpcUrl = env('DASH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'dash_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateDASHAddress($address) {
        $rpcUrl = env('DASH_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getDASHTotalBalance() {
        $rpcUrl = env('DASH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createTRXWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->trx_address)) {
            return $coinAddress->trx_address;
        }

        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $util = new Utility();

        $url = $rpcUrl.'wallet/generateaddress';

        try {
            $result = $util->curl_post($url);

            $coinAddress->trx_address = $result->address;
            $coinAddress->save();

            $tronAddress = new TronAddress();
            $tronAddress->user_id = $user->id;
            $tronAddress->privateKey = $result->privateKey;
            $tronAddress->address = $result->address;
            $tronAddress->hexAddress = $result->hexAddress;
            $tronAddress->save();

            return $result->address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createTRXAdminWallet() {
        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $util = new Utility();

        $url = $rpcUrl.'wallet/generateaddress';

        $adminAddress = AdminSetting::where('name', 'trx_address')->first();
        if ($adminAddress) {
            return false;
        }

        try {
            $result = $util->curl_post($url);

            $tronAddress = new TronAddress();
            $tronAddress->user_id = 1;
            $tronAddress->privateKey = $result->privateKey;
            $tronAddress->address = $result->address;
            $tronAddress->hexAddress = $result->hexAddress;
            $tronAddress->save();

            $adminSetting = new AdminSetting();
            $adminSetting->name = 'trx_address';
            $adminSetting->value = $result->address;
            $adminSetting->save();

            return $result->address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateTRXAddress($address) {
        return true;
    }

    public function getTRXTotalBalance() {
        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $adminAddress = AdminSetting::where('name', 'trx_address')->first();
        if (!$adminAddress) {
            return 0;
        }

        $util = new Utility();

        $address = TronAddress::where('address', $adminAddress->value)->first();
        if (!$address) {
            return 0;
        }

        $url = $rpcUrl.'wallet/getaccount';
        $params = array(
            'address' => $address->hexAddress
        );

        $total_balance = 0;
        try {
            $result = $util->curl_post($url, $params);
            if (isset($result->balance)) {
                $total_balance = $result->balance * $this::TRX_UNIT;
            }
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function getTRC20TotalBalance($coin) {
        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $adminAddress = AdminSetting::where('name', 'trx_address')->first();
        if (!$adminAddress) {
            return 0;
        }

        $util = new Utility();

        $address = TronAddress::where('address', $adminAddress->value)->first();
        if (!$address) {
            return 0;
        }

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

        $total_balance = 0;
        try {
            $result = $util->curl_post($url, $params);

            if($result->result->result) {
                $total_balance = hexdec('0x'.$result->constant_result[0]) / pow(10, $this::TRC20_DECIMALS[$coin]);
            }
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function getTRC10TotalBalance($coin) {
        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $adminAddress = AdminSetting::where('name', 'trx_address')->first();
        if (!$adminAddress) {
            return 0;
        }

        $util = new Utility();

        $address = TronAddress::where('address', $adminAddress->value)->first();
        if (!$address) {
            return 0;
        }

        $url = $rpcUrl.'wallet/getaccount';
        $params = array(
            'address' => $address->hexAddress
        );

        $total_balance = 0;
        try {
            $result = $util->curl_post($url, $params);
            if (isset($result->assetV2)) {
                foreach ($result->assetV2 as $asset) {
                    if ($asset->key == env($coin.'_ASSET_ID')) {
                        $total_balance = $asset->value / pow(10, $this::TRC10_DECIMALS[$coin]);
                        break;
                    }
                }

            }
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createCBCWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->cbc_address)) {
            return $coinAddress->cbc_address;
        }

        $rpcUrl = env('CBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->cbc_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createCBCAdminWallet() {
        $rpcUrl = env('CBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'cbc_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateCBCAddress($address) {
        $rpcUrl = env('CBC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getCBCTotalBalance() {
        $rpcUrl = env('CBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createXZCWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->xzc_address)) {
            return $coinAddress->xzc_address;
        }

        $rpcUrl = env('XZC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->xzc_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createXZCAdminWallet() {
        $rpcUrl = env('XZC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'xzc_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateXZCAddress($address) {
        $rpcUrl = env('XZC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getXZCTotalBalance() {
        $rpcUrl = env('XZC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createZENWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->zen_address)) {
            return $coinAddress->zen_address;
        }

        $rpcUrl = env('ZEN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getnewaddress();

            $coinAddress->zen_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createZENAdminWallet() {
        $rpcUrl = env('ZEN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress();
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'zen_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateZENAddress($address) {
        $rpcUrl = env('ZEN_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getZENTotalBalance() {
        $rpcUrl = env('ZEN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createGXXWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->gxx_address)) {
            return $coinAddress->gxx_address;
        }

        $rpcUrl = env('GXX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->gxx_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createGXXAdminWallet() {
        $rpcUrl = env('GXX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'gxx_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateGXXAddress($address) {
        $rpcUrl = env('GXX_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getGXXTotalBalance() {
        $rpcUrl = env('GXX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createBZXWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->bzx_address)) {
            return $coinAddress->bzx_address;
        }

        $rpcUrl = env('BZX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->bzx_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createBZXAdminWallet() {
        $rpcUrl = env('BZX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'bzx_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateBZXAddress($address) {
        $rpcUrl = env('BZX_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBZXTotalBalance() {
        $rpcUrl = env('BZX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createOOTWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->oot_address)) {
            return $coinAddress->oot_address;
        }

        $rpcUrl = env('OOT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->oot_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createOOTAdminWallet() {
        $rpcUrl = env('OOT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'oot_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateOOTAddress($address) {
        $rpcUrl = env('OOT_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getOOTTotalBalance() {
        $rpcUrl = env('OOT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createABBCWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->abbc_address)) {
            return $coinAddress->abbc_address;
        }

        $rpcUrl = env('ABBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->abbc_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createABBCAdminWallet() {
        $rpcUrl = env('ABBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'abbc_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateABBCAddress($address) {
        $rpcUrl = env('ABBC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getABBCTotalBalance() {
        $rpcUrl = env('ABBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createWIREWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->wire_address)) {
            return $coinAddress->wire_address;
        }

        $rpcUrl = env('WIRE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->wire_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createWIREAdminWallet() {
        $rpcUrl = env('WIRE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'wire_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateWIREAddress($address) {
        $rpcUrl = env('WIRE_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getWIRETotalBalance() {
        $rpcUrl = env('WIRE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function checkCLOPending() {
        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $pendings = CLOPending::all();

        foreach ($pendings as $pending) {

            try {
                $receipt_tx = $jsonRpc->eth_getTransactionReceipt($pending->txid);
            } catch (\Exception $e) {
                continue;
            }

            if ($receipt_tx['status'] == '0x1') {
                $withdraw = Transaction::where([['type', TransactionType::WITHDRAW], ['txid', $pending->txid]])->first();

                if ($withdraw) {
                    $withdraw->status = TransactionStatus::SUCCESS;
                    $withdraw->save();
                }
                $pending->delete();
            } else {
//                $withdraw = Transaction::where([['type', TransactionType::WITHDRAW], ['txid', $pending->txid]])->first();
//
//                if ($withdraw) {
//                    $withdraw->status = TransactionStatus::FAIL;
//                    $withdraw->save();
//                }
//                $pending->delete();
            }
        }
    }

    public function moveCLO($from, $amount) {
        $cloSetting = CLOSetting::pluck('value', 'name');
        $to = $cloSetting['admin_address'];

        if ($from == $to) return;

        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $cloAddress = CLOAddress::where('address', $from)->first();

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, $this::CLO_DECIMALS);

        $result = '';
        try {
            $gasPrice = $jsonRpc->eth_gasPrice();

            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x'.$amountHex,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice))
            ));

            $result = $jsonRpc->personal_sendTransaction(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x'.$amountHex,
                'gas' => $estimateGas,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice))
            ), $cloAddress->secret);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return;
        }

        $pending = new CLOPending();
        $pending->from = $from;
        $pending->to = $to;
        $pending->amount = $amount;
        $pending->txid = $result;
        $pending->coin = 'CLO';

        $pending->save();
    }

    public function checkCLOAddress($address) {
        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $cloSetting = CLOSetting::pluck('value', 'name');

        $balance = 0;
        try {
            $balance = $jsonRpc->eth_getBalance($address, 'latest');
            $balance = hexdec($balance) * $this::CLO_UNIT;
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = CLOPending::where([['from', $address], ['coin', 'CLO']])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);
        if ($balance > $cloSetting['clo_limit']) {
            $this->moveCLO($address,$balance - $cloSetting['clo_min']);
        }
    }

    public function createCLOWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->clo_address)) {
            return $coinAddress->clo_address;
        }
        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);

        try {
            $address = $jsonRpc->personal_newAccount($secret);

            $coinAddress->clo_address = $address;
            $coinAddress->save();

            $ethAddress = new CLOAddress();
            $ethAddress->user_id = $user->id;
            $ethAddress->address = $address;
            $ethAddress->secret = $secret;
            $ethAddress->save();

            return $address;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createCLOAdminWallet() {
        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $utility = new Utility();
        $secret = $utility->random(50);

        try {
            $address = $jsonRpc->personal_newAccount($secret);
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'clo_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        $ethAddress = new CLOAddress();
        $ethAddress->user_id = 1;
        $ethAddress->address = $address;
        $ethAddress->secret = $secret;
        $ethAddress->save();

        return $address;
    }

    public function getCLOBalance($address) {
        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $balance = 0;
        try {
            $balance = $jsonRpc->eth_getBalance($address, 'latest');
            $balance = hexdec($balance) * $this::CLO_UNIT;
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = CLOPending::where([['from', $address], ['coin', 'CLO']])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);

        return $balance;
    }

    public function getCLOTotalBalance() {
        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $cloSetting = CLOSetting::pluck('value', 'name');

        $balance = 0;
        try {
            $balance = $jsonRpc->eth_getBalance($cloSetting['admin_address'], 'latest');
            $balance = hexdec($balance) * $this::CLO_UNIT;
        } catch (\Exception $e) {
            $balance = 0;
            //return response('Error! Please try again.', 403);
        }
        $pendingBalance = CLOPending::where([['from', $cloSetting['admin_address']], ['coin', 'CLO']])->sum('amount');

        $balance -= $pendingBalance;

        $balance = round($balance, 8);

        return $balance;
    }

    public function createWAVESWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->waves_address)) {
            return $coinAddress->waves_address;
        }

        $rpcUrl = env('WAVES_RPC_URL');

        $util = new Utility();

        $url = $rpcUrl.'addresses';

        try {
            $result = $util->curl_post_forwaves($url);

            $coinAddress->waves_address = $result->address;
            $coinAddress->save();

            return $result->address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createWAVESAdminWallet() {
        $rpcUrl = env('WAVES_RPC_URL');

        $util = new Utility();

        $url = $rpcUrl.'addresses';

        $adminAddress = AdminSetting::where('name', 'waves_address')->first();
        if ($adminAddress) {
            return false;
        }

        try {
            $result = $util->curl_post_forwaves($url);

            $adminSetting = new AdminSetting();
            $adminSetting->name = 'waves_address';
            $adminSetting->value = $result->address;
            $adminSetting->save();

            return $result->address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateWAVESAddress($address) {
        return true;
    }

    public function getWAVESTotalBalance() {
        $rpcUrl = env('WAVES_RPC_URL');

        $adminAddress = AdminSetting::where('name', 'waves_address')->first();
        if (!$adminAddress) {
            return 0;
        }

        $util = new Utility();

        $url = $rpcUrl.'addresses/balance/'.$adminAddress->value;

        $total_balance = 0;
        try {
            $result = $util->curl_get_contents($url);
            if (isset($result->balance)) {
                $total_balance = $result->balance * $this::WAVES_UNIT;
            }
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function getCLBTotalBalance() {
        $rpcUrl = env('WAVES_RPC_URL');

        $adminAddress = AdminSetting::where('name', 'waves_address')->first();
        if (!$adminAddress) {
            return 0;
        }

        $util = new Utility();

        $url = $rpcUrl.'assets/balance/'.$adminAddress->value.'/'.$this::CLB_ASSETID;

        $total_balance = 0;
        try {
            $result = $util->curl_get_contents($url);
            if (isset($result->balance)) {
                $total_balance = $result->balance * $this::CLB_UNIT;
            }
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createLGSWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->lgs_address)) {
            return $coinAddress->lgs_address;
        }

        $rpcUrl = env('LGS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->lgs_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createLGSAdminWallet() {
        $rpcUrl = env('LGS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'lgs_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateLGSAddress($address) {
        $rpcUrl = env('LGS_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getLGSTotalBalance() {
        $rpcUrl = env('LGS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createETNWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->etn_paymentid)) {
            return $coinAddress->etn_paymentid;
        }

        $rpcUrl = env('ETN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
//            $jsonRpc->open_wallet(array(
//                'filename' => 'sistemkoin1',
//                'password' => 'Ljoiswjfeoaiwjefoijalixzjcvoijheofujoiw4ur59384u598ujgoaisjgoaisdfj9283u40934i50'
//            ));
            $result = $jsonRpc->make_integrated_address(array());
//            $jsonRpc->close_wallet(array());

            $paymentid = str_pad($result['payment_id'], 64, '0', STR_PAD_LEFT);
            $coinAddress->etn_address = $result['integrated_address'];
            $coinAddress->etn_paymentid = $paymentid;
            $coinAddress->save();

            return $paymentid;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function createETNAdminWallet() {
        $rpcUrl = env('ETN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $result = $jsonRpc->make_integrated_address(array());
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'etn_address';
        $adminSetting->value = $result['integrated_address'];
        $adminSetting->save();

        return $result['integrated_address'];
    }

    public function validateETNAddress($address) {
        return true;
    }

    public function getETNTotalBalance() {
        $rpcUrl = env('ETN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        $total_balance = 0;
        try {
//            $jsonRpc->open_wallet(array(
//                'filename' => 'sistemkoin1',
//                'password' => 'Ljoiswjfeoaiwjefoijalixzjcvoijheofujoiw4ur59384u598ujgoaisjgoaisdfj9283u40934i50'
//            ));
            $result = $jsonRpc->getbalance(array());
//            $jsonRpc->close_wallet(array());
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        $total_balance = $result['balance'] * $this::ETN_UNIT;
        return $total_balance;
    }

    public function createSTKWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->stk_address)) {
            return $coinAddress->stk_address;
        }

        $rpcUrl = env('STK_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->stk_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createSTKAdminWallet() {
        $rpcUrl = env('STK_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'stk_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateSTKAddress($address) {
        $rpcUrl = env('STK_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getSTKTotalBalance() {
        $rpcUrl = env('STK_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function getSCRMainWallet(Request $request) {
        return response()->json([
            'success' => true,
            'address' => 'sistemkoin-scr',
        ]);
    }

    public function createSCRWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->scr_address)) {
            return $coinAddress->scr_address;
        }

        while (true) {
            $tag = rand(100000000, 999999999);

            $exist = CoinAddress::where('scr_address', $tag)->first();

            if (!$exist) {
                $coinAddress->scr_address = $tag;
                $coinAddress->save();

                return $tag;
            }
        }

        return false;
    }

    public function getSCRTotalBalance() {
        $rpcUrl = env('SCR_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->get_account_balance('sistemkoin-scr');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return 0;
        }

        $balance = floatval($result['balance']);
        return $balance;
    }

    public function createXRCWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->xrc_address)) {
            return $coinAddress->xrc_address;
        }

        $rpcUrl = env('XRC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('sistemkoinwallet');

            $coinAddress->xrc_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createXRCAdminWallet() {
        $rpcUrl = env('XRC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $address = $jsonRpc->getnewaddress('admin');
        } catch (\Exception $e) {
            return false;
        }

        $adminSetting = new AdminSetting();
        $adminSetting->name = 'xrc_address';
        $adminSetting->value = $address;
        $adminSetting->save();

        return $address;
    }

    public function validateXRCAddress($address) {
        $rpcUrl = env('XRC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getXRCTotalBalance() {
        $rpcUrl = env('XRC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance('sistemkoinwallet');
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createONTWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->ont_address)) {
            return $coinAddress->ont_address;
        }

        $rpcUrl = env('ONT_CLI_RPC_URL');

        $utility = new Utility();

        try {
            $pwd = $utility->random(32, 0);
            $result = $utility->ont_sigsvr_post($rpcUrl, array(
                'qid' => 't',
                'method' => 'createaccount',
                'pwd' => $pwd,
                'params' => array()
            ));

            $coinAddress->ont_address = $result['account'];
            $coinAddress->save();

            $ontAddress = new ONTAddress();
            $ontAddress->user_id = $user->id;
            $ontAddress->address = $result['account'];
            $ontAddress->password = $pwd;
            $ontAddress->save();

            return $result['account'];
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function createONTAdminWallet() {
        return '';
    }

    public function validateONTAddress($address) {
        return true;
    }

    public function getONTTotalBalance() {
        $rpcUrl = env('ONT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ontSetting = ONTSetting::where('name', 'admin_address')->first();

        try {
            $result = $jsonRpc->getbalance($ontSetting->value);
        } catch (\Exception $e) {
            return 0;
        }

        return $result['ont'] * $this::ONT_UNIT;
    }

    public function getONGTotalBalance() {
        $rpcUrl = env('ONT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ontSetting = ONTSetting::where('name', 'admin_address')->first();

        try {
            $result = $jsonRpc->getbalance($ontSetting->value);
        } catch (\Exception $e) {
            return 0;
        }

        return $result['ong'] * $this::ONG_UNIT;
    }

    public function createGBXWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->gbx_address)) {
            return $coinAddress->gbx_address;
        }

        $rpcUrl = env('GBX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->gbx_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateGBXAddress($address) {
        $rpcUrl = env('GBX_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getGBXTotalBalance() {
        $rpcUrl = env('GBX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }


    public function createCCXWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->ccx_paymentid)) {
            return $coinAddress->ccx_paymentid;
        }

        $util = new Utility();

        try {
            $paymentid = $util->randomHexString(64);

            $coinAddress->ccx_paymentid = $paymentid;
            $coinAddress->save();

            return $paymentid;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validateCCXAddress($address) {
        return true;
    }

    public function getCCXTotalBalance() {
        $rpcUrl = env('CCX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        $ccxSetting = ConcealSetting::where('name', 'wallet_address')->first();
        try {
            $result = $jsonRpc->getBalance(array(
                'address' =>  $ccxSetting->value
            ));
        } catch (\Exception $e) {
            return 0;
        }

        $total_balance = $result['availableBalance'] * $this::CCX_UNIT;
        return $total_balance;
    }

    public function createTMCWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->tmc_address)) {
            return $coinAddress->tmc_address;
        }

        $rpcUrl = env('TMC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->tmc_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateTMCAddress($address) {
        $rpcUrl = env('TMC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getTMCTotalBalance() {
        $rpcUrl = env('TMC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createBITGWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->bitg_address)) {
            return $coinAddress->bitg_address;
        }

        $rpcUrl = env('BITG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->bitg_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateBITGAddress($address) {
        $rpcUrl = env('BITG_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBITGTotalBalance() {
        $rpcUrl = env('BITG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createEXOWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->exo_address)) {
            return $coinAddress->exo_address;
        }

        $rpcUrl = env('EXO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->exo_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateEXOAddress($address) {
        $rpcUrl = env('EXO_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getEXOTotalBalance() {
        $rpcUrl = env('EXO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createBTCONEWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->btcone_address)) {
            return $coinAddress->btcone_address;
        }

        $rpcUrl = env('BTCONE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->btcone_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateBTCONEAddress($address) {
        $rpcUrl = env('BTCONE_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBTCONETotalBalance() {
        $rpcUrl = env('BTCONE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createSINSWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->sins_address)) {
            return $coinAddress->sins_address;
        }

        $rpcUrl = env('SINS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->sins_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateSINSAddress($address) {
        $rpcUrl = env('SINS_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getSINSTotalBalance() {
        $rpcUrl = env('SINS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function getBRAVOMainWallet(Request $request) {
        return response()->json([
            'success' => true,
            'address' => 'sistemkoin',
        ]);
    }

    public function createBRAVOWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->bravo_address)) {
            return $coinAddress->bravo_address;
        }

        while (true) {
            $tag = rand(100000000, 999999999);

            $exist = CoinAddress::where('bravo_address', $tag)->first();

            if (!$exist) {
                $coinAddress->bravo_address = $tag;
                $coinAddress->save();

                return $tag;
            }
        }

        return false;
    }

    public function getBRAVOTotalBalance() {
        $rpcUrl = env('BRAVO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->get_account('sistemkoin');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return 0;
        }

        $balance = floatval($result['balance']);
        return $balance;
    }

    public function getMPGMainWallet(Request $request) {
        $mainAddress = MPGSetting::where('name', 'address')->first()->value;
        return response()->json([
            'success' => true,
            'address' => $mainAddress,
        ]);
    }

    public function createMPGWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->mpg_address)) {
            return $coinAddress->mpg_address;
        }

        while (true) {
            $tag = rand(100000000, 999999999);

            $exist = CoinAddress::where('mpg_address', $tag)->first();

            if (!$exist) {
                $coinAddress->mpg_address = $tag;
                $coinAddress->save();

                return $tag;
            }
        }

        return false;
    }

    public function getMPGTotalBalance() {
        $mainAddress = MPGSetting::where('name', 'address')->first()->value;

        $rpcUrl = env('MPG_RPC_URL');

        $utility = new Utility();
        $url = $rpcUrl.'requestType=getBalance&chain=5&account='.$mainAddress;

        $result = $utility->curl_get_contents($url);

        $balance = 0;
        if (isset($result->balanceNQT)) {
            $balance = $result->balanceNQT * $this::MPG_UNIT;
        }
        return $balance;
    }

    public function createBTKWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->btk_address)) {
            return $coinAddress->btk_address;
        }

        $rpcUrl = env('BTK_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->btk_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateBTKAddress($address) {
        $rpcUrl = env('BTK_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBTKTotalBalance() {
        $rpcUrl = env('BTK_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createVICWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->vic_address)) {
            return $coinAddress->vic_address;
        }

        $rpcUrl = env('VIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->vic_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateVICAddress($address) {
        $rpcUrl = env('VIC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getVICTotalBalance() {
        $rpcUrl = env('VIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createTGNWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->tgn_address)) {
            return $coinAddress->tgn_address;
        }
        $tgnSetting = TGNSetting::find(1);

        $utility = new Utility();
        $rpcUrl = env('TGN_RPC_URL');

        $nowtime = time();

        if ($nowtime > $tgnSetting->auth_validtime) {
            $result = $utility->tgn_curl_post($rpcUrl.'/Wallet/Initialize',
                array(
                    'ApiKey' => $tgnSetting->api_key,
                    'SecretKey' => $tgnSetting->secret_key,
                    'Username' => $tgnSetting->username,
                    'Password' => $tgnSetting->password
                )
            );
            if ($result->access_token) {
                $tgnSetting->authorization = $result->token_type.' '.$result->access_token;
                $tgnSetting->auth_validtime = time() + $result->expires_in;
                $tgnSetting->save();
            }
        }

        $result = $utility->tgn_curl_post($rpcUrl.'/Transaction/GetNewAddress',
            array(
                'ApiKey' => $tgnSetting->api_key,
                'SecretKey' => $tgnSetting->secret_key,
            ),
            true,
            $tgnSetting->authorization
        );

        if ($result->Status) {
            $coinAddress->tgn_address = $result->Data;
            $coinAddress->save();

            return $result->Data;
        } else {
            return false;
        }
    }

    public function validateTGNAddress($address) {
        return true;
    }

    public function getTGNTotalBalance() {
        $tgnSetting = TGNSetting::find(1);

        $utility = new Utility();
        $rpcUrl = env('TGN_RPC_URL');

        $nowtime = time();

        if ($nowtime > $tgnSetting->auth_validtime) {
            $result = $utility->tgn_curl_post($rpcUrl.'/Wallet/Initialize',
                array(
                    'ApiKey' => $tgnSetting->api_key,
                    'SecretKey' => $tgnSetting->secret_key,
                    'Username' => $tgnSetting->username,
                    'Password' => $tgnSetting->password
                )
            );
            if ($result->access_token) {
                $tgnSetting->authorization = $result->token_type.' '.$result->access_token;
                $tgnSetting->auth_validtime = time() + $result->expires_in;
                $tgnSetting->save();
            }
        }

        $result = $utility->tgn_curl_post($rpcUrl.'/Wallet/Balance',
            array(
                'ApiKey' => $tgnSetting->api_key,
                'SecretKey' => $tgnSetting->secret_key,
            ),
            true,
            $tgnSetting->authorization
        );

        if ($result->Status) {
            return floatval($result->Data->Balance);
        } else {
            return 0;
        }
    }

    public function createQCWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->qc_address)) {
            return $coinAddress->qc_address;
        }

        $rpcUrl = env('QC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->qc_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateQCAddress($address) {
        $rpcUrl = env('QC_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getQCTotalBalance() {
        $rpcUrl = env('QC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }

    public function createBCZWallet($user, $coinAddress) {
        if ($coinAddress && !empty($coinAddress->bcz_address)) {
            return $coinAddress->bcz_address;
        }

        $rpcUrl = env('BCZ_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $label = $user->email;

        try {
            $address = $jsonRpc->getaccountaddress($label);

            $coinAddress->bcz_address = $address;
            $coinAddress->save();

            return $address;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            return false;
        }
    }

    public function validateBCZAddress($address) {
        $rpcUrl = env('BCZ_RPC_URL');
        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $validate = $jsonRpc->validateaddress($address);
        } catch (\Exception $e) {
            return false;
        }

        if ($validate['isvalid']) {
            return true;
        } else {
            return false;
        }
    }

    public function getBCZTotalBalance() {
        $rpcUrl = env('BCZ_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $total_balance = 0;
        try {
            $total_balance = $jsonRpc->getbalance();
        } catch (\Exception $e) {
            $total_balance = 0;
        }

        return $total_balance;
    }
}
