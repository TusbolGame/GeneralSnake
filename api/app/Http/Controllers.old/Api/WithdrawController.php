<?php

namespace App\Http\Controllers\Api;

use App\Entities\AchainSetting;
use App\Entities\AdminSetting;
use App\Entities\BitcoenAddress;
use App\Entities\BitwhiteAddress;
use App\Entities\MPGSetting;
use App\Entities\ONTSetting;
use App\Entities\TGNSetting;
use App\Entities\XRCSetting;
use App\Entities\CLOPending;
use App\Entities\CLOSetting;
use App\Entities\CoinAddress;
use App\Entities\CoinWithdrawHistory;
use App\Entities\EthereumAddress;
use App\Entities\EthereumPending;
use App\Entities\EthereumSetting;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\MoveHistory;
use App\Entities\NextyAddress;
use App\Entities\PirlAddress;
use App\Entities\RippleSetting;
use App\Entities\Setting;
use App\Entities\Transaction;
use App\Entities\TronAddress;
use App\Entities\XinAddress;
use App\Entities\XinSetting;
use App\Libs\BitcoenRPC;
use App\Libs\JsonRPCClient;
use App\Http\Controllers\Controller;
use App\Libs\Utility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawController extends Controller
{
    const ETH_UNIT = 1 / 1000000000000000000;
    const ETH_DECIMALS = 18;

    const ERC20_TRANSFER_HEX = '0xa9059cbb';
    const ERC20_BALANCEOF_HEX = '0x70a08231';

    const XRP_UNIT = 1 / 1000000;
    const XRP_INIT_TIME = 946684800;

    const XMR_UNIT = 1 / 1000000000000;

    const XIN_UNIT = 1 / 100000000;
    const XIN_INIT_TIME = 1484020800;

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
    const VEX_CONTRACT = 'CON9XnhX5FtQqGFAa3KgrgkPCCEDPmuzgtSx';

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

    const ETN_UNIT = 1 / 100;

    const ONG_UNIT = 1 / 1000000000;
    const ONT_UNIT = 1;
    const ONG_CONTRACT_ADDRESS = '0200000000000000000000000000000000000000';
    const ONT_CONTRACT_ADDRESS = '0100000000000000000000000000000000000000';

    const CCX_UNIT = 1 / 1000000;

    public function estimateBTCFee($input) {
        return response()->json([
            'success' => false
        ]);

//        $priority = $input['priority'];
//        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;
//
//        if ($priority == 0) {
//            $block = 2;
//        } else if ($priority == 1) {
//            $block = 5;
//        } else {
//            $block = 11;
//        }
//
//        $trans = Transaction::where([['src_coin', 'BTC'], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
//
//        $outputs = array();
//        foreach ($trans as $tran) {
//            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
//        }
//
//        $rpcUrl = env('BITCOIN_RPC_URL');
//
//        $jsonRpc = new JsonRPCClient($rpcUrl);
//
//        try {
//            $fee = $jsonRpc->estimatefee($block);
//        } catch (\Exception $e) {
//            return response($e->getMessage(), 403);
//        }
//
//        $jsonRpc->settxfee($fee);
//
//        try {
//            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
//            $fund = $jsonRpc->fundrawtransaction($hex);
//        } catch (\Exception $e) {
//            return response($e->getMessage(), 403);
//        }
//
//        return response()->json([
//            'success' => true,
//            'fee' => $fund['fee'],
//            'hex' => $fund['hex']
//        ]);
    }

    public function withdrawBTC($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }
        $txid = isset($input['txid']) ? $input['txid'] : '';

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $txid;
        $tran->admin_id = Auth::user()->id;
        $tran->save();
//
//        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;
//
//        $trans = Transaction::where([['src_coin', 'BTC'], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
//
//        $outputs = array();
//        foreach ($trans as $tran) {
//            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
//        }
//
//        if (empty($outputs)) {
//            return response()->json([
//                'success' => false,
//                'error' => 'No Transactions'
//            ]);
//        }
//
//        $rpcUrl = env('BITCOIN_RPC_URL');
//
//        $jsonRpc = new JsonRPCClient($rpcUrl);
//
//        try {
//            $result = $jsonRpc->estimatesmartfee(2);
//            $result = $jsonRpc->settxfee($result['feerate']);
//            $result = $jsonRpc->sendmany("", $outputs);
//        } catch (\Exception $e) {
//            return response($e->getMessage(), 403);
//        }
//
//        foreach ($trans as $tran) {
//            $tran->status = TransactionStatus::SUCCESS;
//            $tran->txid = $result;
//            $tran->admin_id = Auth::user()->id;
//            $tran->save();
//        }
//
//        $withdraw = new CoinWithdrawHistory();
//        $withdraw->coin = 'BTC';
//        $withdraw->datetime = Carbon::now();
//        $withdraw->txid = $result;
//        $withdraw->fee = 0;
//        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawETH($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $ethSetting = EthereumSetting::pluck('value', 'name');
        $from = $ethSetting['admin_address'];
        $secret = $ethSetting['admin_secret'];
        $to = $tran->address;

        $amount = round($tran->src_amount - $tran->fee, 8);

        $sent = false;

        $rpcUrl = env('ETHEREUM_ADMIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $walletController = new WalletController();
        $balance = $walletController->getETHTotalBalance();

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, $this::ETH_DECIMALS);

        $estimateGas = 0;
        $gasPrice = 0;
        try {
            $gasPrice = $jsonRpc->eth_gasPrice();

            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x' . $amountHex,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice) * 2)
            ));
        } catch (\Exception $e) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        $hexEstimate = $estimateGas;
        $hexGas = $gasPrice;

        $estimateGas = hexdec($estimateGas);
        $gasPrice = hexdec($gasPrice) * $this::ETH_UNIT;

        if ($balance > $amount + $estimateGas * $gasPrice * 2) {
            try {
                $result = $jsonRpc->personal_sendTransaction(array(
                    'from' => $from,
                    'to' => $to,
                    'value' => '0x' . $amountHex,
                    'gasPrice' => '0x'.dechex(hexdec($hexGas) * 2)
                ), $secret);
            } catch (\Exception $e) {
                //$tran->status = TransactionStatus::PENDING;
                //$tran->save();

                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        $tran->txid = $result;
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $pending = new EthereumPending();
        $pending->from = $from;
        $pending->to = $to;
        $pending->amount = $amount + $estimateGas * $gasPrice * 2;
        $pending->txid = $result;
        $pending->coin = 'ETH';
        $pending->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'ETH';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $estimateGas * $gasPrice * 2;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawErc20($coin, $input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $ethSetting = EthereumSetting::pluck('value', 'name');
        $from = $ethSetting['admin_address'];
        $secret = $ethSetting['admin_secret'];
        $to = $tran->address;

        $amount = round($tran->src_amount - $tran->fee, 8);

        $rpcUrl = env('ETHEREUM_ADMIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $walletController = new WalletController();
        $eth_balance = $walletController->getETHTotalBalance();
        $erc20_balance = $walletController->getErc20TotalBalance($coin);

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, $this::ERC20_DECIMALS[$coin]);
        $data = $this::ERC20_TRANSFER_HEX . str_pad(substr($to, 2), 64, "0", STR_PAD_LEFT) . str_pad($amountHex, 64, "0", STR_PAD_LEFT);

        $estimateGas = 0;
        $gasPrice = 0;
        try {
            $gasPrice = $jsonRpc->eth_gasPrice();

            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => env($coin . '_CONTRACT_ADDRESS'),
                'value' => '0x0',
                'data' => $data,
                'gasPrice' => '0x'.dechex(hexdec($gasPrice) * 2),
            ));
        } catch (\Exception $e) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        $hexEstimate = $estimateGas;
        $hexGas = $gasPrice;

        $estimateGas = hexdec($estimateGas);
        $gasPrice = hexdec($gasPrice) * $this::ETH_UNIT;

        if ($eth_balance > $estimateGas * $gasPrice * 2 && $erc20_balance >= $amount) {
            try {
                $result = $jsonRpc->personal_sendTransaction(array(
                    'from' => $from,
                    'to' => env($coin . '_CONTRACT_ADDRESS'),
                    'value' => '0x0',
                    'gas' => $hexEstimate,
                    'gasPrice' => '0x'.dechex(hexdec($hexGas) * 2),
                    'data' => $data
                ), $secret);
            } catch (\Exception $e) {
                //$tran->status = TransactionStatus::PENDING;
                //$tran->save();

                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        $tran->txid = $result;
        $tran->admin_id = Auth::user()->id;
        $tran->save();

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

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = $coin;
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $estimateGas * $gasPrice * 2;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawXRP($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('RIPPLE_RPC_URL');
        // $rpcUrl = 'http://213.136.87.120:5005/';

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setRippleFlag();

        $rippleSetting = RippleSetting::all()->pluck('value', 'name');

        try {
            $result = $jsonRpc->account_info(array(
                'account' => $rippleSetting['address']
            ));
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $totalBalance = 0;
        if ($result['status'] == 'success') {
            $totalBalance = $result['account_data']['Balance'];
        }

        if (round($totalBalance - ($tran->src_amount - $tran->fee) / $this::XRP_UNIT, 0) < 20 / $this::XRP_UNIT) {
            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw. Your wallet balance must be always greater than 20 XRP'
            ]);
        }

        $rpcUrl = 'http://213.136.87.120:5005/';

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setRippleFlag();

        $params = array(
            "TransactionType" => "Payment",
            "Account" => $rippleSetting['address'],
            "Amount" => number_format(round(($tran->src_amount - $tran->fee) / $this::XRP_UNIT, 0), 0, '.', ''),
            "Destination" => $tran->address,
            "Fee" => $rippleSetting['withdraw_fee'] / $this::XRP_UNIT
        );

        if ($tran->destination_tag && $tran->destination_tag > 0) {
            $params['DestinationTag'] = $tran->destination_tag;
        }

        try {
            $result = $jsonRpc->submit(array(
                "secret" => $rippleSetting['secret'],
                "offline" => false,
                "tx_json" => $params
            ));
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        if (isset($result['engine_result']) && $result['engine_result'] == 'tesSUCCESS') {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result['tx_json']['hash'];
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XRP';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result['tx_json']['hash'];
            $withdraw->fee = $result['tx_json']['Fee'] * $this::XRP_UNIT;
            $withdraw->save();

            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => isset($result['error_message']) ? $result['error_message'] : (isset($result['engine_result_message']) ? $result['engine_result_message'] : 'Error')
            ]);
        }
    }

    public function estimateLTCFee($input)
    {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        if ($priority == 0) {
            $block = 2;
        } else if ($priority == 1) {
            $block = 5;
        } else {
            $block = 11;
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('LTC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $fee = $jsonRpc->estimatefee($block);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawLTC($input)
    {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('LTC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        try {
//            $result = $jsonRpc->signrawtransaction($hex);
//            $result = $jsonRpc->sendrawtransaction($result['hex']);
//        } catch (\Exception $e) {
//            //return response('Error! Please try again.', 403);
//            return response($e->getMessage(), 403);
//        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'LTC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawDOGE($input)
    {
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('DOGE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->listaccounts();
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        foreach ($result as $key => $value) {
            if ($value > 0 && $key != 'admin') {
                $jsonRpc->move($key, 'admin', $value);
            }
        }

        try {
            $result = $jsonRpc->sendmany('admin', $outputs, 1, 'Sistemkoin');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();
        }

        $txid = $result;
        try {
            $result = $jsonRpc->gettransaction($txid);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'DOGE';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $txid;
        $withdraw->fee = $result['fee'] * (-1);
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function estimateBCHFee($input)
    {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        if ($priority == 0) {
            $block = 2;
        } else if ($priority == 1) {
            $block = 5;
        } else {
            $block = 11;
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('BCH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            // $totalBalance = $jsonRpc->getbalance();
            $fee = $jsonRpc->estimatefee($block);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawBCH($input)
    {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('BCH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->signrawtransaction($hex);
            $result = $jsonRpc->sendrawtransaction($result['hex']);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();
        }

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'BCH';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $fee;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function estimateBTGFee($input)
    {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        if ($priority == 0) {
            $block = 2;
        } else if ($priority == 1) {
            $block = 5;
        } else {
            $block = 11;
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('BTG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $fee = $jsonRpc->estimatefee($block);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawBTG($input)
    {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('BTG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->signrawtransaction($hex);
            $result = $jsonRpc->sendrawtransaction($result['hex']);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();
        }

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'BTG';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $fee;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function estimateZECFee($input)
    {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        if ($priority == 0) {
            $block = 2;
        } else if ($priority == 1) {
            $block = 5;
        } else {
            $block = 11;
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('ZEC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        try {
//            $fee = $jsonRpc->estimatefee($block);
//        } catch (\Exception $e) {
//            return response($e->getMessage(), 403);
//        }
//
//        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawZEC($input) {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('ZEC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        try {
//            $result = $jsonRpc->signrawtransaction($hex);
//            $result = $jsonRpc->sendrawtransaction($result['hex']);
//        } catch (\Exception $e) {
//            //return response('Error! Please try again.', 403);
//            return response($e->getMessage(), 403);
//        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'ZEC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

//        foreach ($trans as $tran) {
//            $tran->status = TransactionStatus::SUCCESS;
//            $tran->txid = $result;
//            $tran->save();
//        }
//
//        $withdraw = new CoinWithdrawHistory();
//        $withdraw->coin = 'ZEC';
//        $withdraw->datetime = Carbon::now();
//        $withdraw->txid = $result;
//        $withdraw->fee = $fee;
//        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function estimateDGBFee($input)
    {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        if ($priority == 0) {
            $block = 2;
        } else if ($priority == 1) {
            $block = 5;
        } else {
            $block = 11;
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('DGB_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
//
//        try {
//            $fee = $jsonRpc->estimatefee($block);
//        } catch (\Exception $e) {
//            return response($e->getMessage(), 403);
//        }
//
//        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawDGB($input)
    {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('DGB_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->signrawtransaction($hex);
            $result = $jsonRpc->sendrawtransaction($result['hex']);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();
        }

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'DGB';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $fee;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawXMR($input)
    {
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            array_push($outputs, array(
                'amount' => round($tran->src_amount - $tran->fee, 8) / $this::XMR_UNIT,
                'address' => $tran->address
            ));
        }

        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            // $totalBalance = $jsonRpc->getbalance();
            $result = $jsonRpc->transfer(array(
                'destinations' => $outputs,
                'mixin' => 4,
                'get_tk_key' => true
            ));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            // return response($e->getMessage(), 403);
        }

        foreach ($trans as $tran) {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result['tx_hash'];
            $tran->admin_id = Auth::user()->id;
            $tran->save();
        }

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'XMR';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $result['fee'] * $this::XMR_UNIT;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawXLM($input) {
        return response()->json([
            'success' => false,
        ]);
    }

    public function withdrawBEN($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('BITCOEN_RPC_URL');

        $jsonRpc = new BitcoenRPC($rpcUrl);

        $adminAddress = AdminSetting::where('name', 'ben_address')->first();

        $from = $adminAddress->value;
        $to = $tran->address;
        $amount = round($tran->src_amount - $tran->fee, 8);

        $balance = 0;
        try {
            $wallet = $jsonRpc->getWalletInfo($from);
            $balance = BitcoenRPC::mil2Ben($wallet['balance']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw.'
            ]);
        }

        if ($balance >= $amount) {
            $address = BitcoenAddress::where('address', $from)->first();
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'error' => 'You can not withdraw.'
                ]);
            }

            try {
                $result = $jsonRpc->instantTransactionData($to, BitcoenRPC::ben2Mil($amount), $from, $address->public, $address->private);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'You can not withdraw.'
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw.'
            ]);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result['index'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'BEN';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result['index'];
        $withdraw->fee = 0;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function transferXIN($from, $to, $amount, $tran)
    {
        $rpcUrl = env('XIN_RPC_URL');

        $utility = new Utility();

        $balance = 0;
        $url = $rpcUrl."requestType=getAccount&account=".$from;
        $result = $utility->curl_get_contents($url);

        if (isset($result->balanceTQT)) {
            $balance += $result->balanceTQT * $this::XIN_UNIT;
        } else {
            return false;
        }

        if ($balance < $amount + 1) {
            return false;
        }

        $address = XinAddress::where('address', $from)->first();

        if (!$address) {
            return false;
        }

        $url = $rpcUrl."requestType=sendToken&recipient=".$to."&amountTQT=".$amount/$this::XIN_UNIT."&feeTQT=100000000&secretPhrase=".$address->passphrase."&deadline=1440&recipientPublicKey=".$tran->publicKey;
        $result = $utility->curl_post($url);

        if (isset($result->transaction) && $result->transaction != '') {

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result->transaction;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XIN';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result->transaction;
            $withdraw->fee = 1;
            $withdraw->save();

            return true;
        } else {
            return false;
        }
    }

    public function withdrawXIN($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $addresses = CoinAddress::whereNotNull('xin_address')->pluck('xin_address', 'user_id');

        $sent = false;
        if (isset($addresses[$tran->user_id]) && $this->transferXIN($addresses[$tran->user_id], $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
            $sent = true;
        } else {
            $adminAddresses = AdminSetting::where('name', 'xin_address')->get();

            foreach ($adminAddresses as $address) {
                if ($this->transferXIN($address->value, $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
                    $sent = true;
                    break;
                }
            }

            if (!$sent) {
                foreach ($addresses as $key=>$address) {
                    if ($key == $tran->user_id) continue;

                    if ($this->transferXIN($address, $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
                        $sent = true;
                        break;
                    }
                }
            }
        }

        if ($sent) {
            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw.'
            ]);
        }
    }


    public function estimateFRSTFee($input) {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        if ($priority == 0) {
            $block = 2;
        } else if ($priority == 1) {
            $block = 5;
        } else {
            $block = 11;
        }

        $trans = Transaction::where([['src_coin', 'FRST'], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('FIRSTCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        try {
//            $fee = $jsonRpc->estimatefee($block);
//        } catch (\Exception $e) {
//            return response($e->getMessage(), 403);
//        }
//
//        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => 0,
            'hex' => $hex
        ]);
    }

    public function withdrawFRST($input) {
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('FIRSTCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->listaccounts();
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        foreach ($result as $key=>$value) {
            if ($value > 0 && $key != 'admin') {
                $jsonRpc->move($key, 'admin', $value);
            }
        }

        try {
            $result = $jsonRpc->sendmany('admin', $outputs, 1, 'Sistemkoin');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();
        }

        $txid = $result;
//        try {
//            $result = $jsonRpc->gettransaction($txid);
//        } catch (\Exception $e) {
//            //return response('Error! Please try again.', 403);
//            return response($e->getMessage(), 403);
//        }

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'FRST';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $txid;
        $withdraw->fee = 0.01;
        $withdraw->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function transferUSDT($from, $to, $amount, $tran)
    {
        $rpcUrl = env('OMNICORE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->omni_getbalance($from, $this::USDT_PROPERTY);
            $balance = $result['balance'];
        } catch (\Exception $e) {
            return false;
        }

        if ($balance < $amount) {
            return false;
        }

        try {
            $result = $jsonRpc->omni_send($from, $to, $this::USDT_PROPERTY, strval($amount));
        } catch (\Exception $e) {
            return false;
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result;
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'USDT';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = 0;
        $withdraw->save();

        return true;
    }

    public function withdrawUSDT($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $addresses = CoinAddress::whereNotNull('usdt_address')->pluck('usdt_address', 'user_id');

        $sent = false;
        if (isset($addresses[$tran->user_id]) && $this->transferUSDT($addresses[$tran->user_id], $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
            $sent = true;
        } else {
            $adminAddresses = AdminSetting::where('name', 'usdt_address')->get();

            foreach ($adminAddresses as $address) {
                if ($this->transferUSDT($address->value, $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
                    $sent = true;
                    break;
                }
            }

            if (!$sent) {
                foreach ($addresses as $key=>$address) {
                    if ($key == $tran->user_id) continue;

                    if ($this->transferUSDT($address, $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
                        $sent = true;
                        break;
                    }
                }
            }
        }

        if ($sent) {
            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw.'
            ]);
        }
    }

    public function estimateGRSFee($input) {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $trans = Transaction::where([['src_coin', 'GRS'], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('GRS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $fee = 0.0001;

        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawGRS($input) {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('GRS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        try {
//            $result = $jsonRpc->signrawtransaction($hex);
//            $result = $jsonRpc->sendrawtransaction($result['hex']);
//        } catch (\Exception $e) {
//            //return response('Error! Please try again.', 403);
//            return response($e->getMessage(), 403);
//        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'GRS';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }


    public function estimateSYSFee($input) {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $trans = Transaction::where([['src_coin', 'SYS'], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('SYS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        $fee = 0.00001;
//
//        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawSYS($input) {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('SYS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'SYS';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function estimateXSNFee($input) {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $trans = Transaction::where([['src_coin', 'XSN'], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('XSN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        $fee = 0.00001;
//
//        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawXSN($input) {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('XSN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XSN';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }


    public function transferBTW($from, $to, $amount, $tran)
    {
        $rpcUrl = env('BITWHITE_RPC_URL');

        $util = new Utility();

        $url = $rpcUrl."accounts/getBalance?address=".$from;

        $result = $util->curl_get_contents($url);

        if ($result->success) {
            $balance = $result->balance * $this::BTW_UNIT;
        } else {
            return false;
        }

        $btwAddress = BitwhiteAddress::where('address', $from)->first();

        if (!$btwAddress) {
            return false;
        }

        if ($balance > $amount + $this::BTW_FEE) {

            $url = $rpcUrl."transactions";

            $result = $util->curl_put($url, array(
                'secret' => $btwAddress->secret,
                'amount' => $amount / $this::BTW_UNIT,
                'recipientId' => $to
            ));

            if ($result->success) {
                $tran->status = TransactionStatus::SUCCESS;
                $tran->txid = $result->transactionId;
                $tran->admin_id = Auth::user()->id;
                $tran->save();

                $withdraw = new CoinWithdrawHistory();
                $withdraw->coin = 'BTW';
                $withdraw->datetime = Carbon::now();
                $withdraw->txid = $result->transactionId;
                $withdraw->fee = $this::BTW_FEE;
                $withdraw->save();
            } else {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    public function withdrawBTW($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $addresses = CoinAddress::whereNotNull('btw_address')->pluck('btw_address', 'user_id');

        $sent = false;
        if (isset($addresses[$tran->user_id]) && $this->transferBTW($addresses[$tran->user_id], $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
            $sent = true;
        } else {
            $adminAddresses = AdminSetting::where('name', 'btw_address')->get();

            foreach ($adminAddresses as $address) {
                if ($this->transferBTW($address->value, $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
                    $sent = true;
                    break;
                }
            }

            if (!$sent) {
                foreach ($addresses as $key=>$address) {
                    if ($key == $tran->user_id) continue;

                    if ($this->transferBTW($address, $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
                        $sent = true;
                        break;
                    }
                }
            }
        }

        if ($sent) {
            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw.'
            ]);
        }
    }

    public function estimateMEDICFee($input) {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $trans = Transaction::where([['src_coin', 'MEDIC'], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('MEDIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

//        $fee = 0.00001;
//
//        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawMEDIC($input) {
//        $hex = $input['hex'];
//        $fee = $input['fee'];
        $fee = 0.00001;
        $id = $input['id'];

        $rpcUrl = env('MEDIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;
//            try {
//                $result = $jsonRpc->gettransaction($txid);
//            } catch (\Exception $e) {
//                //return response('Error! Please try again.', 403);
//                return response($e->getMessage(), 403);
//            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid ;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'MEDIC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function estimateBTCPFee($input) {
        $priority = $input['priority'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        if ($priority == 0) {
            $block = 2;
        } else if ($priority == 1) {
            $block = 5;
        } else {
            $block = 11;
        }

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();

        $outputs = array();
        foreach ($trans as $tran) {
            $outputs[$tran->address] = (isset($outputs[$tran->address]) ? $outputs[$tran->address] : 0) + round($tran->src_amount - $tran->fee, 8);
        }

        $rpcUrl = env('BTCP_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $fee = 0.00001;

        $jsonRpc->settxfee($fee);

        try {
            $hex = $jsonRpc->createrawtransaction(array(), $outputs);
            $fund = $jsonRpc->fundrawtransaction($hex);
        } catch (\Exception $e) {
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
            'fee' => $fund['fee'],
            'hex' => $fund['hex']
        ]);
    }

    public function withdrawBTCP($input) {
        $hex = $input['hex'];
        $fee = $input['fee'];
        $ids = isset($input['ids']) ? explode(',', $input['ids']) : null;

        $rpcUrl = env('BTCP_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->whereIn('id', $ids)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'BTCP';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function transferPIRL($from, $to, $amount, $tran)
    {
        $rpcUrl = env('PIRL_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        try {
            $result = $jsonRpc->eth_getBalance($from, 'latest');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return false;
        }

        $balance = hexdec($result) * $this::PIRL_UNIT;

        $estimateGas = 0;
        $gasPrice = 0;
        try {
            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x'.dechex($amount / $this::PIRL_UNIT)
            ));
            $gasPrice = $jsonRpc->eth_gasPrice();
        } catch (\Exception $e) {
            return false;
        }

        $estimateGas = hexdec($estimateGas);
        $gasPrice = hexdec($gasPrice) * $this::PIRL_UNIT;

        $pirlAddress = PirlAddress::where('address', $from)->first();

        if (!$pirlAddress) {
            return false;
        }

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, 18);
        if ($balance > $amount + $estimateGas * $gasPrice) {
            try {
                $result = $jsonRpc->personal_sendTransaction(array(
                    'from' => $from,
                    'to' => $to,
                    'value' => '0x'.$amountHex
                ), $pirlAddress->secret);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result;
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'PIRL';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $estimateGas * $gasPrice;
        $withdraw->save();

        return true;
    }

    public function withdrawPIRL($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $adminAddresses = AdminSetting::where('name', 'pirl_address')->get();

        $sent = false;
        foreach ($adminAddresses as $address) {
            if ($this->transferPIRL($address->value, $tran->address, $tran->src_amount - $tran->fee, $tran) != false) {
                $sent = true;
                break;
            }
        }

        if ($sent) {
            return response()->json([
                'success' => true,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw.'
            ]);
        }
    }

    public function withdrawTPAY($input) {
        $fee = 0.0001;
        $id = $input['id'];

        $rpcUrl = env('TPAY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid ;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'TPAY';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawBTS($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('BITSHARES_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->list_account_balances($this::BTS_ACCOUNT_NAME);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $bts_balance = 0;

        foreach ($result as $rec) {
            if ($rec['asset_id'] == $this::BTS_ASSETID) {
                $bts_balance = $rec['amount'] * $this::BTS_UNIT;
                break;
            }
        }

        $withdrawAmount = round($tran->src_amount - $tran->fee, 8);
        if ($withdrawAmount > $bts_balance) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough.'
            ]);
        }

        try {
            $rs = $jsonRpc->unlock($this::BTS_WALLET_PWD);
            $result = $jsonRpc->transfer($this::BTS_ACCOUNT_NAME, $tran->address, number_format($withdrawAmount,4,'.',''), $this::BTS_ASSETID, $tran->destination_tag, true);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            $result = $jsonRpc->lock();
            return response($e->getMessage(), 403);
        }

        try {
            $rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            // return response($e->getMessage(), 403);
        }

        var_dump($result);

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = ''; //!empty($result) ? $result[0]['op']['id'] : ''; //$result['operations']['id'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'BTS';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = ''; //!empty($result) ? $result[0]['op']['id'] : '';
        $withdraw->fee = 0; //!empty($result) ? $result[0]['op']['op'][1]['fee']['amount'] * $this::BTS_UNIT : 0;
        $withdraw->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function withdrawCVN($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('BITSHARES_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->list_account_balances($this::BTS_ACCOUNT_NAME);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $cvn_balance = 0;

        foreach ($result as $rec) {
            if ($rec['asset_id'] == $this::CVN_ASSETID) {
                $cvn_balance = $rec['amount'] * $this::CVN_UNIT;
                break;
            }
        }

        $withdrawAmount = round($tran->src_amount - $tran->fee, 8);
        if ($withdrawAmount > $cvn_balance) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough.'
            ]);
        }

        try {
            $rs = $jsonRpc->unlock($this::BTS_WALLET_PWD);
            $result = $jsonRpc->transfer($this::BTS_ACCOUNT_NAME, $tran->address, number_format($withdrawAmount,4,'.',''), $this::CVN_ASSETID, $tran->destination_tag, true);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            $result = $jsonRpc->lock();
            return response($e->getMessage(), 403);
        }

        try {
            $rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            // return response($e->getMessage(), 403);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = ''; //!empty($result) ? $result[0]['op']['id'] : ''; //$result['operations']['id'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'CVN';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = ''; //!empty($result) ? $result[0]['op']['id'] : '';
        $withdraw->fee = 0; //!empty($result) ? $result[0]['op']['op'][1]['fee']['amount'] * $this::BTS_UNIT : 0;
        $withdraw->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function withdrawDEEX($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('BITSHARES_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->list_account_balances($this::BTS_ACCOUNT_NAME);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $deex_balance = 0;

        foreach ($result as $rec) {
            if ($rec['asset_id'] == $this::DEEX_ASSETID) {
                $deex_balance = $rec['amount'] * $this::DEEX_UNIT;
                break;
            }
        }

        $withdrawAmount = round($tran->src_amount - $tran->fee, 4);
        if ($withdrawAmount > $deex_balance) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough.'
            ]);
        }

        try {
            $rs = $jsonRpc->unlock($this::BTS_WALLET_PWD);
            $result = $jsonRpc->transfer($this::BTS_ACCOUNT_NAME, $tran->address, number_format($withdrawAmount,4,'.',''), $this::DEEX_ASSETID, $tran->destination_tag, true);
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            $result = $jsonRpc->lock();
            return response($e->getMessage(), 403);
        }

        try {
            // $result = $jsonRpc->get_account_history($this::BTS_ACCOUNT_NAME, 1);
            $rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            $result = '';
            // return response($e->getMessage(), 403);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = ''; // !empty($result) ? $result[0]['op']['id'] : ''; //$result['operations']['id'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'DEEX';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = ''; //!empty($result) ? $result[0]['op']['id'] : '';
        $withdraw->fee = 0; // !empty($result) ? $result[0]['op']['op'][1]['fee']['amount'] * $this::BTS_UNIT : 0;
        $withdraw->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function withdrawHTML($input) {
        $fee = 0.0001;
        $id = $input['id'];

        $rpcUrl = env('HTML_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid ;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XVG';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $result['fee'] ? $result['fee'] : 0;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawXVG($input) {
        $fee = 0.1;
        $id = $input['id'];

        $rpcUrl = env('XVG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XVG';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawACT($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('ACHAIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $withdrawAmount = $tran->src_amount - $tran->fee;
        $actSetting = AchainSetting::where('name', 'wallet_pwd')->first();
        try {
            $result = $jsonRpc->wallet_unlock('10', $actSetting->value);
            $result = $jsonRpc->wallet_transfer_to_address(number_format($withdrawAmount,5,'.',''), 'ACT', 'sistemkoin', $tran->address);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result['entry_id'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'ACT';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result['entry_id'];
        $withdraw->fee = 0.01;        
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }



    public function withdrawVEX($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('ACHAIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $withdrawAmount = round($tran->src_amount - $tran->fee, 5);
        $actSetting = AchainSetting::where('name', 'wallet_pwd')->first();

        $balance = AchainSetting::where('name', 'vex_balance')->first();
        if ($withdrawAmount > $balance->value) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough for withdraw'
            ]);
        }

        try {
            $result = $jsonRpc->wallet_unlock('10', $actSetting->value);
            $result = $jsonRpc->call_contract($this::VEX_CONTRACT, 'sistemkoin', 'transfer_to', $tran->address.'|'.$withdrawAmount, 'ACT', 1);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result['entry_id'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'VEX';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result['entry_id'];
        $withdraw->fee = 0.02;
        $withdraw->save();

        $balance->value = $balance->value - $withdrawAmount;
        $balance->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawXGOX($input) {
        $fee = 0.0001;
        $id = $input['id'];

        $rpcUrl = env('XGOX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XGOX';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawPAC($input) {
        $fee = 0.0001;
        $id = $input['id'];

        $rpcUrl = env('PAC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'PAC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawMRI($input) {
        $fee = 0.1;
        $id = $input['id'];

        $rpcUrl = env('MRI_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'MRI';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawDASH($input) {
        $id = $input['id'];

        $rpcUrl = env('DASH_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'DASH';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawTRX($input) {
        $id = $input['id'];
        $hexAddress = $input['hexAddress'];

        if (!$hexAddress) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $util = new Utility();

        $adminAddress = AdminSetting::where('name', 'trx_address')->first();
        if (!$adminAddress) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $trxAddress = TronAddress::where('address', $adminAddress->value)->first();
        if (!$trxAddress) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $controller = new WalletController();
        $balance = $controller->getTRXTotalBalance();

        $amount = round($tran->src_amount - $tran->fee, 6);

        if ($balance < $amount + 0.1) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        try {
            $url = $rpcUrl . 'wallet/createtransaction';
            $params = array(
                'to_address' => $hexAddress,
                'owner_address' => $trxAddress->hexAddress,
                'amount' => $amount / $this::TRX_UNIT
            );
            $result = $util->curl_post($url, $params);

            if (!isset($result->txID)) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }

            $txid = $result->txID;

            $url = $rpcUrl.'wallet/gettransactionsign';
            $params = array(
                'transaction' => $result,
                'privateKey' => $trxAddress->privateKey
            );
            $result = $util->curl_post($url, $params);

            if (!isset($result->signature)) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }

            $url = $rpcUrl.'wallet/broadcasttransaction';
            $result = $util->curl_post($url, $result);

            if (isset($result->result) && $result->result) {
                $tran->status = TransactionStatus::SUCCESS;
                $tran->txid = $txid;
                $tran->admin_id = Auth::user()->id;
                $tran->save();

                $withdraw = new CoinWithdrawHistory();
                $withdraw->coin = 'TRX';
                $withdraw->datetime = Carbon::now();
                $withdraw->txid = $txid;
                $withdraw->fee = 0.1;
                $withdraw->save();
            } else {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }
        }  catch (\Exception $e) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawTRC20($coin, $input) {
        $id = $input['id'];
        $hexAddress = $input['hexAddress'];

        if (!$hexAddress) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $util = new Utility();

        $adminAddress = AdminSetting::where('name', 'trx_address')->first();
        if (!$adminAddress) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $trxAddress = TronAddress::where('address', $adminAddress->value)->first();
        if (!$trxAddress) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $controller = new WalletController();
        $balance = $controller->getTRC20TotalBalance($coin);

        $amount = round($tran->src_amount - $tran->fee, 6);

        if ($balance < $amount + 0.1) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        try {
            $utility = new Utility();
            $amountHex = $utility->convertHex($amount, $this::TRC20_DECIMALS[$coin]);

            $rpcUrl = 'https://super.guildchat.io/';
            $url = $rpcUrl.'wallet/triggersmartcontract';
            $abiEncoder = str_pad(substr($hexAddress, 2), 64, "0", STR_PAD_LEFT) . str_pad($amountHex, 64, "0", STR_PAD_LEFT);

            $params = array(
                'contract_address' => env($coin.'_CONTRACT_HEX_ADDRESS'),
                'function_selector' => 'transfer(address,uint256)',
                'parameter' => $abiEncoder,
                'fee_limit' => 1000000,
                'call_value' => 0,
                'owner_address' => $trxAddress->hexAddress
            );

            $result = $util->curl_post($url, $params);

            if(!$result->result->result) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => 'Error'
                ]);
            }
            $transaction = $result->transaction;
            $txid = $transaction->txID;

            $url = $rpcUrl.'wallet/gettransactionsign';
            $params = array(
                'transaction' => $transaction,
                'privateKey' => $trxAddress->privateKey
            );
            $result = $util->curl_post($url, $params);

            if (!isset($result->signature)) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }

            $url = $rpcUrl.'wallet/broadcasttransaction';
            $result = $util->curl_post($url, $result);

            if (isset($result->result) && $result->result) {
                $tran->status = TransactionStatus::SUCCESS;
                $tran->txid = $txid;
                $tran->admin_id = Auth::user()->id;
                $tran->save();
            } else {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }
        }  catch (\Exception $e) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawTRC10($coin, $input) {
        $id = $input['id'];
        $hexAddress = $input['hexAddress'];

        if (!$hexAddress) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $util = new Utility();

        $adminAddress = AdminSetting::where('name', 'trx_address')->first();
        if (!$adminAddress) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $trxAddress = TronAddress::where('address', $adminAddress->value)->first();
        if (!$trxAddress) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $controller = new WalletController();
        $balance = $controller->getTRC10TotalBalance($coin);

        $amount = round($tran->src_amount - $tran->fee, 6);

        if ($balance < $amount + 0.1) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        try {
            $url = $rpcUrl . 'wallet/transferasset';
            $params = array(
                'to_address' => $hexAddress,
                'owner_address' => $trxAddress->hexAddress,
                'asset_name' => env($coin.'_ASSET_ID_HEX'),
                'amount' => $amount * pow(10, $this::TRC10_DECIMALS[$coin])
            );
            $result = $util->curl_post($url, $params);

            if (!isset($result->txID)) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }

            $txid = $result->txID;

            $url = $rpcUrl.'wallet/gettransactionsign';
            $params = array(
                'transaction' => $result,
                'privateKey' => $trxAddress->privateKey
            );
            $result = $util->curl_post($url, $params);

            if (!isset($result->signature)) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }

            $url = $rpcUrl.'wallet/broadcasttransaction';
            $result = $util->curl_post($url, $result);

            if (isset($result->result) && $result->result) {
                $tran->status = TransactionStatus::SUCCESS;
                $tran->txid = $txid;
                $tran->admin_id = Auth::user()->id;
                $tran->save();

                $withdraw = new CoinWithdrawHistory();
                $withdraw->coin = 'TRX';
                $withdraw->datetime = Carbon::now();
                $withdraw->txid = $txid;
                $withdraw->fee = 0.1;
                $withdraw->save();
            } else {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => isset($result->Error) ? $result->Error : 'Please try again'
                ]);
            }
        }  catch (\Exception $e) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawCBC($input) {
        $id = $input['id'];

        $rpcUrl = env('CBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'CBC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawXZC($input) {
        $id = $input['id'];

        $rpcUrl = env('XZC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XZC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function transferNTY($from, $to, $amount, $tran)
    {
        $rpcUrl = env('NTY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        try {
            $result = $jsonRpc->eth_getBalance($from, 'latest');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return false;
        }

        $balance = hexdec($result) * $this::NTY_UNIT;

        $estimateGas = 0;
        $gasPrice = 0;
        try {
            $estimateGas = $jsonRpc->eth_estimateGas(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x'.dechex($amount / $this::NTY_UNIT)
            ));
            $gasPrice = $jsonRpc->eth_gasPrice();
        } catch (\Exception $e) {
            return false;
        }

        $estimateGas = hexdec($estimateGas);
        $gasPrice = hexdec($gasPrice) * $this::NTY_UNIT;

        $pirlAddress = PirlAddress::where('address', $from)->first();

        if (!$pirlAddress) {
            return false;
        }

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, 18);
        if ($balance > $amount + $estimateGas * $gasPrice) {
            try {
                $result = $jsonRpc->personal_sendTransaction(array(
                    'from' => $from,
                    'to' => $to,
                    'value' => '0x'.$amountHex
                ), $pirlAddress->secret);
            } catch (\Exception $e) {
                return false;
            }
        } else {
            return false;
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result;
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'NTY';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $estimateGas * $gasPrice;
        $withdraw->save();

        return true;
    }

    public function withdrawNTY($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $adminAddresses = AdminSetting::where('name', 'nty_address')->get();

        $rpcUrl = env('NTY_RPC_URL');
        $to = $tran->address;
        $amount = $tran->src_amount - $tran->fee;

        $sent = false;

        foreach ($adminAddresses as $address) {
            $from = $address->value;

            $jsonRpc = new JsonRPCClient($rpcUrl);
            try {
                $result = $jsonRpc->eth_getBalance($from, 'latest');
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                continue;
            }

            $balance = hexdec($result) * $this::NTY_UNIT;

            $estimateGas = 0;
            $gasPrice = 0;
            try {
                $estimateGas = $jsonRpc->eth_estimateGas(array(
                    'from' => $from,
                    'to' => $to,
                    'value' => '0x'.dechex($amount / $this::NTY_UNIT)
                ));
                $gasPrice = $jsonRpc->eth_gasPrice();
            } catch (\Exception $e) {
                continue;
            }

            $estimateGas = hexdec($estimateGas);
            $gasPrice = hexdec($gasPrice) * $this::NTY_UNIT;

            $ntyAddress = NextyAddress::where('address', $from)->first();

            if (!$ntyAddress) {
                continue;
            }

            $utility = new Utility();
            $amountHex = $utility->convertHex($amount, 18);

            if ($balance > $amount + $estimateGas * $gasPrice) {
                try {
                    $result = $jsonRpc->personal_sendTransaction(array(
                        'from' => $from,
                        'to' => $to,
                        'value' => '0x'.$amountHex
                    ), $ntyAddress->secret);
                } catch (\Exception $e) {
                    continue;
                }
            } else {
                continue;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'NTY';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result;
            $withdraw->fee = $estimateGas * $gasPrice;
            $withdraw->save();

            $sent = true;
            break;
        }

        if ($sent) {
            return response()->json([
                'success' => true,
            ]);
        } else {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'You can not withdraw.'
            ]);
        }
    }

    public function withdrawZEN($input) {
        $id = $input['id'];

        $rpcUrl = env('ZEN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'ZEN';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawGXX($input) {
        $id = $input['id'];

        $rpcUrl = env('GXX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'GXX';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawBZX($input) {
        $id = $input['id'];

        $rpcUrl = env('BZX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'BZX';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawOOT($input) {
        $id = $input['id'];

        $rpcUrl = env('OOT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'OOT';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawABBC($input) {
        $id = $input['id'];

        $rpcUrl = env('ABBC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'ABBC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawWIRE($input) {
        $id = $input['id'];

        $rpcUrl = env('WIRE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'WIRE';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }
    public function withdrawCLO($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $cloSetting = CLOSetting::pluck('value', 'name');
        $from = $cloSetting['admin_address'];
        $secret = $cloSetting['admin_secret'];
        $to = $tran->address;

        $amount = round($tran->src_amount - $tran->fee, 8);

        $sent = false;

        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $walletController = new WalletController();
        $balance = $walletController->getCLOTotalBalance();

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, $this::CLO_DECIMALS);

        $estimateGas = 0;
        $gasPrice = 0;
//        try {
//            $gasPrice = $jsonRpc->eth_gasPrice();
//
//            $estimateGas = $jsonRpc->eth_estimateGas(array(
//                'from' => $from,
//                'to' => $to,
//                'value' => '0x' . $amountHex,
//                'gasPrice' => '0x'.dechex(hexdec($gasPrice))
//            ));
//        } catch (\Exception $e) {
//            $tran->status = TransactionStatus::PENDING;
//            $tran->save();
//
//            return response()->json([
//                'success' => false,
//                'error' => $e->getMessage()
//            ]);
//        }
//
//        $hexEstimate = $estimateGas;
//        $hexGas = $gasPrice;
//
//        $estimateGas = hexdec($estimateGas);
//        $gasPrice = hexdec($gasPrice) * $this::CLO_UNIT;

        if ($balance > $amount + $estimateGas * $gasPrice) {
            try {
                $result = $jsonRpc->personal_sendTransaction(array(
                    'from' => $from,
                    'to' => $to,
                    'value' => '0x' . $amountHex
                ), $secret);
            } catch (\Exception $e) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();

            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        $tran->txid = $result;
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $pending = new CLOPending();
        $pending->from = $from;
        $pending->to = $to;
        $pending->amount = $amount + $estimateGas * $gasPrice;
        $pending->txid = $result;
        $pending->coin = 'CLO';
        $pending->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'CLO';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result;
        $withdraw->fee = $estimateGas * $gasPrice;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawWAVES($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $rpcUrl = env('WAVES_RPC_URL');

        $util = new Utility();

        $adminAddress = AdminSetting::where('name', 'waves_address')->first();
        if (!$adminAddress) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $controller = new WalletController();
        $balance = $controller->getWAVESTotalBalance();

        $amount = round($tran->src_amount - $tran->fee, 8);

        if ($balance < $amount + 0.001) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        try {
            $url = $rpcUrl . 'transactions/sign';
            $params = array(
                'type' => 4,
                'sender' => $adminAddress->value,
                'recipient' => $tran->address,
                'amount' => $amount / $this::WAVES_UNIT,
                'fee' => 100000,
                'attachment' => ''
            );

            $result = $util->curl_post_forwaves($url, $params);

            if (!isset($result->id)) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => 'Please try again'
                ]);
            }

            $result = $util->curl_post($rpcUrl.'transactions/broadcast', $result);

            if (isset($result->id) && $result->id) {
                $txid = $result->id;

                $tran->status = TransactionStatus::SUCCESS;
                $tran->txid = $txid;
                $tran->admin_id = Auth::user()->id;
                $tran->save();

                $withdraw = new CoinWithdrawHistory();
                $withdraw->coin = 'WAVES';
                $withdraw->datetime = Carbon::now();
                $withdraw->txid = $txid;
                $withdraw->fee = 0.001;
                $withdraw->save();
            } else {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => 'Please try again'
                ]);
            }
        }  catch (\Exception $e) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawCLB($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $tran->status = TransactionStatus::PROCESSING;
        $tran->save();

        $rpcUrl = env('WAVES_RPC_URL');

        $util = new Utility();

        $adminAddress = AdminSetting::where('name', 'waves_address')->first();
        if (!$adminAddress) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $controller = new WalletController();
        $balance = $controller->getCLBTotalBalance();

        $amount = round($tran->src_amount - $tran->fee, 8);

        if ($balance < $amount + 0.1) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough'
            ]);
        }

        try {
            $url = $rpcUrl . 'transactions/sign';
            $params = array(
                'type' => 4,
                'sender' => $adminAddress->value,
                'recipient' => $tran->address,
                'amount' => $amount / $this::CLB_UNIT,
                'assetId' => $this::CLB_ASSETID,
                'feeAssetId' => $this::CLB_ASSETID,
                'fee' => 10000000,
                'attachment' => ''
            );

            $result = $util->curl_post_forwaves($url, $params);

            if (!isset($result->id)) {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => $result->message
                ]);
            }

            $result = $util->curl_post($rpcUrl.'transactions/broadcast', $result);

            if (isset($result->id) && $result->id) {
                $txid = $result->id;

                $tran->status = TransactionStatus::SUCCESS;
                $tran->txid = $txid;
                $tran->admin_id = Auth::user()->id;
                $tran->save();

                $withdraw = new CoinWithdrawHistory();
                $withdraw->coin = 'CLB';
                $withdraw->datetime = Carbon::now();
                $withdraw->txid = $txid;
                $withdraw->fee = 0.1;
                $withdraw->save();
            } else {
                $tran->status = TransactionStatus::PENDING;
                $tran->save();

                return response()->json([
                    'success' => false,
                    'error' => 'Please try again'
                ]);
            }
        }  catch (\Exception $e) {
            $tran->status = TransactionStatus::PENDING;
            $tran->save();
            return response($e->getMessage(), 403);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawLGS($input) {
        $id = $input['id'];

        $rpcUrl = env('LGS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'LGS';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawETN($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $outputs = array();
        array_push($outputs, array(
            'amount' => round($tran->src_amount - $tran->fee, 2) / $this::ETN_UNIT,
            'address' => $tran->address
        ));

        $rpcUrl = env('ETN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            // $totalBalance = $jsonRpc->getbalance();
//            $jsonRpc->open_wallet(array(
//                'filename' => 'sistemkoin1',
//                'password' => 'Ljoiswjfeoaiwjefoijalixzjcvoijheofujoiw4ur59384u598ujgoaisjgoaisdfj9283u40934i50'
//            ));
            $result = $jsonRpc->transfer_split(array(
                'destinations' => $outputs,
                'mixin' => 4,
                'get_tx_key' => true,
                'new_algorithm' => true,
                'payment_id' => $tran-> payment_id ? $tran-> payment_id : ''
            ));
//            $jsonRpc->close_wallet(array());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            // return response($e->getMessage(), 403);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result['tx_hash_list'][0];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'ETN';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result['tx_hash_list'][0];
        $withdraw->fee = $result['fee_list'][0] * $this::ETN_UNIT;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawSTK($input) {
        $id = $input['id'];

        $rpcUrl = env('STK_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'STK';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }


    public function withdrawSCR($input)
    {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('SCR_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->get_account_balance('sistemkoin-scr');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $scr_balance = floatval($result['balance']);

        $withdrawAmount = round($tran->src_amount - $tran->fee, 8);
        if ($withdrawAmount > $scr_balance) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough.'
            ]);
        }

        try {
            $rs = $jsonRpc->unlock('14753698');
            $result = $jsonRpc->transfer('sistemkoin-scr', $tran->address, number_format($withdrawAmount,9,'.','').' SCR', $tran->destination_tag, true);
        } catch (\Exception $e) {
            $result = $jsonRpc->lock();
            return response($e->getMessage(), 403);
        }

        try {
            $rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            // return response($e->getMessage(), 403);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result['transaction_id']; //!empty($result) ? $result[0]['op']['id'] : ''; //$result['operations']['id'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'SCR';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result['transaction_id']; //!empty($result) ? $result[0]['op']['id'] : '';
        $withdraw->fee = 0; //!empty($result) ? $result[0]['op']['op'][1]['fee']['amount'] * $this::BTS_UNIT : 0;
        $withdraw->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function withdrawXRC($input) {
        $id = $input['id'];

        $rpcUrl = env('XRC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();

        $xrcSetting = XRCSetting::pluck('value', 'name');
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress('sistemkoinwallet', $xrcSetting['password'], $tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'XRC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = 0;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawONT($input) {
        $id = $input['id'];

        $rpcUrl = env('ONT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $cliUrl = env('ONT_CLI_RPC_URL');

        $utility = new Utility();

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();

        $ontSetting = ONTSetting::pluck('value', 'name');
        foreach ($trans as $tran) {
            try {
                $result = $utility->ont_sigsvr_post($cliUrl, array(
                    'qid' => 't',
                    'method' => 'signativeinvoketx',
                    'account' => $ontSetting['admin_address'],
                    'pwd' => $ontSetting['admin_pwd'],
                    'params' => array(
                        'gas_price' => 500,
                        'gas_limit' => 20000,
                        'address' => $this::ONT_CONTRACT_ADDRESS,
                        'method' => 'transfer',
                        'version' => 0,
                        'params' => array(
                            array(
                                array(
                                    $ontSetting['admin_address'], $tran->address, number_format(round($tran->src_amount - $tran->fee, 8), 0, '.', ''),
                                )
                            )
                        )
                    )
                ));

                $result = $jsonRpc->sendrawtransaction($result['signed_tx']);
            } catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'ONT';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = 0;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawONG($input) {
        $id = $input['id'];

        $rpcUrl = env('ONT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $cliUrl = env('ONT_CLI_RPC_URL');

        $utility = new Utility();

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();

        $ontSetting = ONTSetting::pluck('value', 'name');
        foreach ($trans as $tran) {
            try {
                $result = $utility->ont_sigsvr_post($cliUrl, array(
                    'qid' => 't',
                    'method' => 'signativeinvoketx',
                    'account' => $ontSetting['admin_address'],
                    'pwd' => $ontSetting['admin_pwd'],
                    'params' => array(
                        'gas_price' => 500,
                        'gas_limit' => 20000,
                        'address' => $this::ONG_CONTRACT_ADDRESS,
                        'method' => 'transfer',
                        'version' => 0,
                        'params' => array(
                            array(
                                array(
                                    $ontSetting['admin_address'], $tran->address, number_format(round($tran->src_amount - $tran->fee, 8) / $this::ONG_UNIT, 0, '.', ''),
                                )
                            )
                        )
                    )
                ));

                $result = $jsonRpc->sendrawtransaction($result['signed_tx']);
            } catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'ONG';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = 0;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawGBX($input) {
        $id = $input['id'];

        $rpcUrl = env('GBX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'GBX';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawCCX($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $outputs = array();
        array_push($outputs, array(
            'amount' => round($tran->src_amount - $tran->fee, 6) / $this::CCX_UNIT,
            'address' => $tran->address
        ));

        $rpcUrl = env('CCX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $result = $jsonRpc->sendTransaction(array(
                'anonymity' => 0,
                'fee' => 10000,
                'unlockTime' => 0,
                'paymentId' => $tran-> payment_id ? $tran-> payment_id : '',
                'addresses' => array(
                    'ccx7g3ymku66qzZyKLaSf4CY5KLDKXpns3Ukad7sMz2pKx8GaBjbzibj4pUKPnSkfQKPodgSEk2y9dKo9a8ArRoc2MXQ5RYyYX'
                ),
                'transfers' => $outputs,
            ));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            // return response($e->getMessage(), 403);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result['transactionHash'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'CCX';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result['transactionHash'];
        $withdraw->fee = 10000 * $this::CCX_UNIT;
        $withdraw->save();

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawTMC($input) {
        $id = $input['id'];

        $rpcUrl = env('TMC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'TMC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawBITG($input) {
        $id = $input['id'];

        $rpcUrl = env('BITG_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'BITG';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawEXO($input) {
        $id = $input['id'];

        $rpcUrl = env('EXO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'EXO';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawBTCONE($input) {
        $id = $input['id'];

        $rpcUrl = env('BTCONE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'BTCONE';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawSINS($input) {
        $id = $input['id'];

        $rpcUrl = env('SINS_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'SINS';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawBRAVO($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('BRAVO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $result = $jsonRpc->get_account('sistemkoin');
        } catch (\Exception $e) {
            //return response('Error! Please try again.', 403);
            return response($e->getMessage(), 403);
        }

        $bravo_balance = floatval($result['balance']);

        $withdrawAmount = round($tran->src_amount - $tran->fee, 8);
        if ($withdrawAmount > $bravo_balance) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough.'
            ]);
        }

        try {
            $rs = $jsonRpc->unlock('BRAVOklsdjfoaijwr0293i490iasdofjasoildfjoaiwejfoiaw');
            $result = $jsonRpc->transfer('sistemkoin', $tran->address, number_format($withdrawAmount,3,'.','').' BRAVO', $tran->destination_tag, true);
        } catch (\Exception $e) {
            $result = $jsonRpc->lock();
            return response($e->getMessage(), 403);
        }

        try {
            $rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            // return response($e->getMessage(), 403);
        }

        $tran->status = TransactionStatus::SUCCESS;
        $tran->txid = $result['block_num'];
        $tran->admin_id = Auth::user()->id;
        $tran->save();

        $withdraw = new CoinWithdrawHistory();
        $withdraw->coin = 'BRAVO';
        $withdraw->datetime = Carbon::now();
        $withdraw->txid = $result['block_num'];
        $withdraw->fee = 0;
        $withdraw->save();

        return response()->json([
            'success' => true
        ]);
    }

    public function withdrawMPG($input) {
        $id = $input['id'];

        $tran = Transaction::find($id);
        if (!$tran || $tran->type != TransactionType::WITHDRAW || $tran->status != TransactionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'error' => 'Not Found'
            ]);
        }

        $rpcUrl = env('MPG_RPC_URL');

        $mpgSetting = MPGSetting::pluck('value', 'name');

        $utility = new Utility();

        $address = $tran->address;
        $publicKey = $tran->publicKey;

        $url = $rpcUrl.'requestType=getBalance&chain=5&account='.$mpgSetting['address'];
        $result = $utility->curl_get_contents($url);

        $balance = 0;
        if (isset($result->balanceNQT)) {
            $balance = $result->balanceNQT * $this::MPG_UNIT;
        }

        $withdrawAmount = round($tran->src_amount - $tran->fee, 8);
        if ($withdrawAmount > $balance) {
            return response()->json([
                'success' => false,
                'error' => 'Balance is not enough.'
            ]);
        }

        $url = $rpcUrl."requestType=sendMoney&chain=5&recipient=".$address."&amountNQT=".$withdrawAmount/$this::MPG_UNIT."&feeNQT=-1&secretPhrase=".$mpgSetting['secret']."&deadline=1440&recipientPublicKey=".$publicKey.'&method=post';
        if (!is_null($tran->detination_tag)) {
            $url.='&message='.$tran->detination_tag.'&messageIsText=true';
        }
        $result = $utility->curl_post($url);

        if (isset($result->fullHash)) {
            $feeNQT = $result->transactionJSON->feeNQT;

            $url = $rpcUrl."requestType=sendMoney&chain=5&recipient=".$address."&amountNQT=".$withdrawAmount/$this::MPG_UNIT."&feeNQT=".$feeNQT."&secretPhrase=".$mpgSetting['secret']."&deadline=1440&recipientPublicKey=".$publicKey.'&method=post';
            if (!is_null($tran->detination_tag)) {
                $url.='&message='.$tran->detination_tag.'&messageIsText=true';
            }
            $result = $utility->curl_post($url);

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $result->fullHash;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'MPG';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $result->fullHash;
            $withdraw->fee = 0;
            $withdraw->save();

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => 'Try again'
            ]);
        }
    }

    public function withdrawBTK($input) {
        $id = $input['id'];

        $rpcUrl = env('BTK_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'BTK';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawVIC($input) {
        $id = $input['id'];

        $rpcUrl = env('VIC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'VIC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawTGN($input) {
        $id = $input['id'];

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

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            $result = $utility->tgn_curl_post($rpcUrl.'/Transaction/Send',
                array(
                    'ApiKey' => $tgnSetting->api_key,
                    'SecretKey' => $tgnSetting->secret_key,
                    'SendAddress' => $tran->address,
                    'Amount' => round($tran->src_amount - $tran->fee, 8)
                ),
                true,
                $tgnSetting->authorization
            );

            if ($result->Status) {
                $txid = $result->TransactionHash;

                $tran->status = TransactionStatus::SUCCESS;
                $tran->txid = $txid;
                $tran->admin_id = Auth::user()->id;
                $tran->save();

                $withdraw = new CoinWithdrawHistory();
                $withdraw->coin = 'TGN';
                $withdraw->datetime = Carbon::now();
                $withdraw->txid = $txid;
                $withdraw->fee = $result->TransactionFees;
                $withdraw->save();

                return response()->json([
                    'success' => true,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result->Message
                ]);
            }
        }
    }

    public function withdrawQC($input) {
        $id = $input['id'];

        $rpcUrl = env('QC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'QC';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function withdrawBCZ($input) {
        $id = $input['id'];

        $rpcUrl = env('BCZ_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $trans = Transaction::where([['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])->where('id', $id)->get();
        foreach ($trans as $tran) {
            try {
                $result = $jsonRpc->sendtoaddress($tran->address, round($tran->src_amount - $tran->fee, 8));
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return response($e->getMessage(), 403);
            }

            $txid = $result;

            try {
                $result = $jsonRpc->gettransaction($txid);
                $fee = $result['fee'] * (-1);
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                $fee = 0;
            }

            $tran->status = TransactionStatus::SUCCESS;
            $tran->txid = $txid;
            $tran->admin_id = Auth::user()->id;
            $tran->save();

            $withdraw = new CoinWithdrawHistory();
            $withdraw->coin = 'BCZ';
            $withdraw->datetime = Carbon::now();
            $withdraw->txid = $txid;
            $withdraw->fee = $fee;
            $withdraw->save();
        }

        return response()->json([
            'success' => true,
        ]);
    }
}

