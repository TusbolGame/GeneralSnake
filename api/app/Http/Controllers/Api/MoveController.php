<?php

namespace App\Http\Controllers\Api;

use App\Entities\AdminSetting;
use App\Entities\BitcoenAddress;
use App\Entities\BitwhiteAddress;
use App\Entities\CoinAddress;
use App\Entities\CoinWithdrawHistory;
use App\Entities\EthereumAddress;
use App\Entities\EthereumSetting;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\MoveHistory;
use App\Entities\NextyAddress;
use App\Entities\ONTAddress;
use App\Entities\ONTSetting;
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

class MoveController extends Controller
{
    const ERC20_TRANSFER_HEX = '0xa9059cbb';
    const ERC20_BALANCEOF_HEX = '0x70a08231';
    const ETH_UNIT = 1 / 1000000000000000000;
    const TRX_UNIT = 1 / 1000000;
    const TRC20_DECIMALS = array(
        'IGG' => 6
    );
    const TRC10_DECIMALS = array(
        'BTT' => 6
    );

    const WRC_UNIT = 1 / 1000000;

    const WPR_UNIT = 1 / 1000000000000000000;

    const DRG_UNIT = 1 / 100000000;
    
    const XRP_UNIT = 1 / 1000000;
    const XRP_INIT_TIME = 946684800;
    
    const XMR_UNIT = 1 / 1000000000000;

    const XIN_UNIT = 1 / 100000000;
    const XIN_INIT_TIME = 1484020800;

    const USDT_PROPERTY = 31;

    const BTW_UNIT = 1 / 100000000;
    const BTW_FEE = 0.1;

    const LNC_UNIT = 1 / 1000000000000000000;
    const MTC_UNIT = 1 / 1000000000000000000;
    const RLX_UNIT = 1 / 1000000000000000000;
    const HBZ_UNIT = 1 / 1000000000000000000;
    const FTC_UNIT = 1;
    const FRV_UNIT = 1 / 100000000;

    const PIRL_UNIT = 1 / 1000000000000000000;
    const NTY_UNIT = 1 / 1000000000000000000;

    const CLB_UNIT = 1 / 100000000;
    const WAVES_UNIT = 1 / 100000000;
    const ADMN_UNIT = 1 / 100000000;
    const CLB_ASSETID = 'CfMBo6GAZZhXs3bbS64NwAor24CuXwYVGJ34SSkPKHVf';
    const ADMN_ASSETID = '2ad6RZP9DVntNGNkS5so4oax2x2yZV4ZTr2yCNrpj1VN';

    const ONG_UNIT = 1 / 1000000000;
    const ONT_UNIT = 1;
    const ONG_CONTRACT_ADDRESS = '0200000000000000000000000000000000000000';
    const ONT_CONTRACT_ADDRESS = '0100000000000000000000000000000000000000';

    public function moveETH($from, $to, $amount) {
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethAddress = EthereumAddress::where('address', $from)->first();

        $utility = new Utility();
        $amountHex = $utility->convertHex($amount, 18);

        $result = '';
        try {
            $result = $jsonRpc->personal_sendTransaction(array(
                'from' => $from,
                'to' => $to,
                'value' => '0x'.$amountHex
            ), $ethAddress->secret);
        } catch (\Exception $e) {
            var_dump('false');
        }

        var_dump($result);
    }
    public function moveCoinToMainWallet($coin, Request $request) {
        $address = $request->input('address');

        if (!$address) {
            return response()->json([
                'success' => false,
                'error' => 'Address Not Valid'
            ]);
        }

        if ($coin == 'BTW') {
            $btwAddress = BitwhiteAddress::where('address', $address)->first();
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'error' => 'Address Not Valid'
                ]);
            }

            $adminAddress = AdminSetting::where('name', 'btw_address')->first();
            if (!$adminAddress) {
                return response()->json([
                    'success' => false,
                    'error' => 'Admin address Not Valid'
                ]);
            }

            $rpcUrl = env('BITWHITE_RPC_URL');

            $util = new Utility();

            $url = $rpcUrl."accounts/getBalance?address=".$address;

            $result = $util->curl_get_contents($url);

            if ($result->success) {
                $balance = $result->balance * $this::BTW_UNIT;
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Address Not Valid'
                ]);
            }

            if ($balance > $this::BTW_FEE) {
                $url = $rpcUrl."transactions";

                $result = $util->curl_put($url, array(
                    'secret' => $btwAddress->secret,
                    'amount' => ($balance - $this::BTW_FEE) / $this::BTW_UNIT,
                    'recipientId' => $adminAddress->value
                ));

                if ($result->success) {
                    return response()->json([
                        'success' => true
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'Transaction Failed'
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Balance is not enough'
                ]);
            }
        } else if ($coin == 'XIN') {
            $rpcUrl = env('XIN_RPC_URL');

            $utility = new Utility();

            $balance = 0;
            $url = $rpcUrl."requestType=getAccount&account=".$address;
            $result = $utility->curl_get_contents($url);

            if (isset($result->balanceTQT)) {
                $balance += $result->balanceTQT * $this::XIN_UNIT;
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Transaction Failed'
                ]);
            }

            if ($balance <= 1) {
                return response()->json([
                    'success' => false,
                    'error' => 'Balance is not enough'
                ]);
            }

            $xinAddress = XinAddress::where('address', $address)->first();

            if (!$xinAddress) {
                return response()->json([
                    'success' => false,
                    'error' => 'Address Not Valid'
                ]);
            }

            $adminAddress = AdminSetting::where('name', 'xin_address')->first();
            $publicKey = XinAddress::where('address', $adminAddress->value)->first()->publicKey;

            $url = $rpcUrl."requestType=sendToken&recipient=".$adminAddress->value."&amountTQT=".($balance-1)/$this::XIN_UNIT."&feeTQT=100000000&secretPhrase=".$xinAddress->passphrase."&deadline=1440&recipientPublicKey=".$publicKey;
            $result = $utility->curl_post($url);

            if (isset($result->transaction) && $result->transaction != '') {
                return response()->json([
                    'success' => true
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Transaction Failed'
                ]);
            }
        } else if ($coin == 'PIRL') {
            $adminAddress = AdminSetting::where('name', 'pirl_address')->first();

            $rpcUrl = env('PIRL_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);
            try {
                $result = $jsonRpc->eth_getBalance($address, 'latest');
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return false;
            }
            $balance = hexdec($result) * $this::PIRL_UNIT;

            $estimateGas = 0;
            $gasPrice = 0;
            try {
                $estimateGas = $jsonRpc->eth_estimateGas(array(
                    'from' => $address,
                    'to' => $adminAddress->value,
                    'value' => '0x'.dechex($balance / $this::PIRL_UNIT)
                ));
                $gasPrice = $jsonRpc->eth_gasPrice();
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                return false;
            }

            $estimateGas = hexdec($estimateGas);
            $gasPrice = hexdec($gasPrice) * $this::PIRL_UNIT;

            $pirlAddress = PirlAddress::where('address', $address)->first();

            if (!$pirlAddress) {
                return false;
            }

            $utility = new Utility();
            $amountHex = $utility->convertHex($balance -  1, 18);
            try {
                $result = $jsonRpc->personal_sendTransaction(array(
                    'from' => $address,
                    'to' => $adminAddress->value,
                    'gas' => '0x'.dechex($estimateGas),
                    'value' => '0x'.$amountHex
                ), $pirlAddress->secret);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false
                ]);
            }

            $move = new MoveHistory();
            $move->coin = 'PIRL';
            $move->datetime = Carbon::now();
            $move->txid = $result;
            $move->fee = $estimateGas * $gasPrice;
            $move->from = $address;
            $move->to = $adminAddress->value;
            $move->save();

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'BEN') {
            $adminAddress = AdminSetting::where('name', 'ben_address')->first();

            $rpcUrl = env('BITCOEN_RPC_URL');

            $jsonRpc = new BitcoenRPC($rpcUrl);

            $balance = 0;

            try {
                $wallet = $jsonRpc->getWalletInfo($address);
                $balance = BitcoenRPC::mil2Ben($wallet['balance']);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }

            $address = BitcoenAddress::where('address', $address)->first();
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'No address'
                ]);
            }

            try {
                $result = $jsonRpc->instantTransactionData($adminAddress->value, BitcoenRPC::ben2Mil($balance), $address->address, $address->public, $address->private);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }

            $move = new MoveHistory();
            $move->coin = 'BEN';
            $move->datetime = Carbon::now();
            $move->txid = $result['index'];
            $move->fee = 0;
            $move->from = $address->address;
            $move->to = $adminAddress->value;
            $move->amount = $balance;
            $move->save();

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'TRX') {
            $adminAddress = AdminSetting::where('name', 'trx_address')->first();

            $rpcUrl = env('TRX_FULLNODE_RPC_URL');

            $balance = 0;

            $address = TronAddress::where('address', $address)->first();
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }

            $util = new Utility();
            try {
                $url = $rpcUrl.'wallet/getaccount';
                $params = array(
                    'address' => $address->hexAddress
                );
                $result = $util->curl_post($url, $params);

                if (isset($result->balance)) {
                    $balance = $result->balance * $this::TRX_UNIT;
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            if ($balance < 0.1) {
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance'
                ]);
            }

            $adminAddress = TronAddress::where('address', $adminAddress->value)->first();
            if (!$adminAddress) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }

            try {
                $url = $rpcUrl . 'wallet/createtransaction';
                $params = array(
                    'to_address' => $adminAddress->hexAddress,
                    'owner_address' => $address->hexAddress,
                    'amount' => ($balance - 0.1) / $this::TRX_UNIT
                );
                $result = $util->curl_post($url, $params);

                if (!isset($result->txID)) {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                $txid = $result->txID;

                $url = $rpcUrl.'wallet/gettransactionsign';
                $params = array(
                    'transaction' => $result,
                    'privateKey' => $address->privateKey
                );
                $result = $util->curl_post($url, $params);

                if (!isset($result->signature)) {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                $url = $rpcUrl.'wallet/broadcasttransaction';
                $result = $util->curl_post($url, $result);

                if (isset($result->result) && $result->result) {
                    $move = new MoveHistory();
                    $move->coin = 'TRX';
                    $move->datetime = Carbon::now();
                    $move->txid = $txid;
                    $move->fee = 0.1;
                    $move->from = $address->address;
                    $move->to = $adminAddress->address;
                    $move->amount = $balance;
                    $move->save();
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }
            }  catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'IGG') {
            $adminAddress = AdminSetting::where('name', 'trx_address')->first();

            $rpcUrl = 'https://super.guildchat.io/';
            $fullNodeRpcUrl = env('TRX_FULLNODE_RPC_URL');

            $balance = 0;
            $trx_balance = 0;

            $address = TronAddress::where('address', $address)->first();
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }

            $util = new Utility();
            try {
                $url = $fullNodeRpcUrl.'wallet/getaccount';
                $params = array(
                    'address' => $address->hexAddress
                );
                $result = $util->curl_post($url, $params);

                if (isset($result->balance)) {
                    $trx_balance = $result->balance * $this::TRX_UNIT;
                }

                $url = $rpcUrl.'wallet/triggersmartcontract';
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
                    $balance = hexdec('0x'.$result->constant_result[0]);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            if ($balance < 1000000) {
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance'
                ]);
            }

            $adminAddress = TronAddress::where('address', $adminAddress->value)->first();
            if (!$adminAddress) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }

            if ($trx_balance < 0.05) {
                $url = $fullNodeRpcUrl . 'wallet/createtransaction';
                $params = array(
                    'to_address' => $address->hexAddress,
                    'owner_address' => $adminAddress->hexAddress,
                    'amount' => 0.1 / $this::TRX_UNIT
                );
                $result = $util->curl_post($url, $params);

                if (!isset($result->txID)) {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                $txid = $result->txID;

                $url = $fullNodeRpcUrl.'wallet/gettransactionsign';
                $params = array(
                    'transaction' => $result,
                    'privateKey' => $adminAddress->privateKey
                );
                $result = $util->curl_post($url, $params);

                if (!isset($result->signature)) {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                $url = $fullNodeRpcUrl.'wallet/broadcasttransaction';
                $result = $util->curl_post($url, $result);

                if (isset($result->result) && $result->result) {
                    $move = new MoveHistory();
                    $move->coin = 'TRX';
                    $move->datetime = Carbon::now();
                    $move->txid = $txid;
                    $move->fee = 0.1;
                    $move->from = $adminAddress->address;
                    $move->to = $address->address;
                    $move->amount = 0.1;
                    $move->save();
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'error' => 'Not enough TRX balance. Please try again 1 min later'
                ]);
            }

            try {
                $amountHex = $util->convertHex($balance, 0);

                $rpcUrl = 'https://super.guildchat.io/';
                $url = $rpcUrl.'wallet/triggersmartcontract';
                $abiEncoder = str_pad(substr($adminAddress->hexAddress, 2), 64, "0", STR_PAD_LEFT) . str_pad($amountHex, 64, "0", STR_PAD_LEFT);

                $params = array(
                    'contract_address' => env($coin.'_CONTRACT_HEX_ADDRESS'),
                    'function_selector' => 'transfer(address,uint256)',
                    'parameter' => $abiEncoder,
                    'fee_limit' => 1000000,
                    'call_value' => 0,
                    'owner_address' => $address->hexAddress
                );

                $result = $util->curl_post($url, $params);

                if(!$result->result->result) {
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
                    'privateKey' => $address->privateKey
                );
                $result = $util->curl_post($url, $params);

                if (!isset($result->signature)) {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                $url = $rpcUrl.'wallet/broadcasttransaction';
                $result = $util->curl_post($url, $result);

                if (isset($result->result) && $result->result) {
                    $move = new MoveHistory();
                    $move->coin = 'IGG';
                    $move->datetime = Carbon::now();
                    $move->txid = $txid;
                    $move->from = $address->address;
                    $move->to = $adminAddress->address;
                    $move->amount = $balance / pow(10, $this::TRC20_DECIMALS['IGG']);
                    $move->fee = 0;
                    $move->save();

                    $addr = TronAddress::where('hexAddress', $address->hexAddress)->first();
                    if ($addr) {
                        $addr->igg_deposited = 0;
                        $addr->save();
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }
            }  catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        }  else if ($coin == 'BTT') {
            $adminAddress = AdminSetting::where('name', 'trx_address')->first();

            $rpcUrl = env('TRX_FULLNODE_RPC_URL');

            $balance = 0;

            $address = TronAddress::where('address', $address)->first();
            if (!$address) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }

            $util = new Utility();
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
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }

            if ($balance < 0.1) {
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance'
                ]);
            }

            $adminAddress = TronAddress::where('address', $adminAddress->value)->first();
            if (!$adminAddress) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }

            try {
                $url = $rpcUrl . 'wallet/transferasset';
                $params = array(
                    'to_address' => $adminAddress->hexAddress,
                    'owner_address' => $address->hexAddress,
                    'asset_name' => env('BTT_ASSET_ID_HEX'),
                    'amount' => $balance * pow(10, $this::TRC10_DECIMALS[$coin])
                );
                $result = $util->curl_post($url, $params);

                if (!isset($result->txID)) {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                $txid = $result->txID;

                $url = $rpcUrl.'wallet/gettransactionsign';
                $params = array(
                    'transaction' => $result,
                    'privateKey' => $address->privateKey
                );
                $result = $util->curl_post($url, $params);

                if (!isset($result->signature)) {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }

                $url = $rpcUrl.'wallet/broadcasttransaction';
                $result = $util->curl_post($url, $result);

                if (isset($result->result) && $result->result) {
                    $move = new MoveHistory();
                    $move->coin = 'TRX';
                    $move->datetime = Carbon::now();
                    $move->txid = $txid;
                    $move->fee = 0.1;
                    $move->from = $address->address;
                    $move->to = $adminAddress->address;
                    $move->amount = $balance;
                    $move->save();

                    $addr = TronAddress::where('hexAddress', $address->hexAddress)->first();
                    if ($addr) {
                        $addr->btt_deposited = 0;
                        $addr->save();
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }
            }  catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'NTY') {
            $adminAddress = AdminSetting::where('name', 'nty_address')->first();

            $rpcUrl = env('NTY_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);
            try {
                $result = $jsonRpc->eth_getBalance($address, 'latest');
            } catch (\Exception $e) {
                //return response('Error! Please try again.', 403);
                return false;
            }
            $balance = hexdec($result) * $this::NTY_UNIT;

            $estimateGas = 0;
            $gasPrice = 0;
            try {
                $estimateGas = $jsonRpc->eth_estimateGas(array(
                    'from' => $address,
                    'to' => $adminAddress->value,
                    'value' => '0x'.dechex($balance / $this::NTY_UNIT)
                ));
                $gasPrice = $jsonRpc->eth_gasPrice();
            } catch (\Exception $e) {
                var_dump($e->getMessage());
                return false;
            }

            $estimateGas = hexdec($estimateGas);
            $gasPrice = hexdec($gasPrice) * $this::NTY_UNIT;

            $ntyAddress = NextyAddress::where('address', $address)->first();

            if (!$ntyAddress) {
                return false;
            }

            $utility = new Utility();
            $amountHex = $utility->convertHex($balance -  1, 18);
            try {
                $result = $jsonRpc->personal_sendTransaction(array(
                    'from' => $address,
                    'to' => $adminAddress->value,
                    'gas' => '0x'.dechex($estimateGas),
                    'value' => '0x'.$amountHex
                ), $ntyAddress->secret);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false
                ]);
            }

            $move = new MoveHistory();
            $move->coin = 'NTY';
            $move->datetime = Carbon::now();
            $move->txid = $result;
            $move->fee = $estimateGas * $gasPrice;
            $move->from = $address;
            $move->to = $adminAddress->value;
            $move->save();

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'WAVES') {
            $adminAddress = AdminSetting::where('name', 'waves_address')->first();

            $rpcUrl = env('WAVES_RPC_URL');

            $balance = 0;

            $util = new Utility();

            $url = $rpcUrl.'addresses/balance/'.$address;

            try {
                $result = $util->curl_get_contents($url);
                if (isset($result->balance)) {
                    $balance = $result->balance * $this::WAVES_UNIT;
                }
            } catch (\Exception $e) {
                $balance = 0;
            }

            if ($balance < 0.001) {
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance'
                ]);
            }

            try {
                $url = $rpcUrl . 'transactions/sign';
                $params = array(
                    'type' => 4,
                    'sender' => $address,
                    'recipient' => $adminAddress->value,
                    'amount' => ($balance - 0.001) / $this::WAVES_UNIT,
                    'fee' => 100000,
                    'attachment' => ''
                );

                $result = $util->curl_post_forwaves($url, $params);

                if (!isset($result->id)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Please try again'
                    ]);
                }

                $result = $util->curl_post($rpcUrl.'transactions/broadcast', $result);

                if (isset($result->id) && $result->id) {
                    $move = new MoveHistory();
                    $move->coin = 'WAVES';
                    $move->datetime = Carbon::now();
                    $move->txid = $result->id;
                    $move->fee = 0.001;
                    $move->from = $address;
                    $move->to = $adminAddress->value;
                    $move->amount = $balance;
                    $move->save();
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }
            }  catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'CLB') {
            $adminAddress = AdminSetting::where('name', 'waves_address')->first();

            $rpcUrl = env('WAVES_RPC_URL');

            $balance = 0;

            $util = new Utility();

            $url = $rpcUrl.'assets/balance/'.$address.'/'.$this::CLB_ASSETID;

            try {
                $result = $util->curl_get_contents($url);
                if (isset($result->balance)) {
                    $balance = $result->balance * $this::WAVES_UNIT;
                }
            } catch (\Exception $e) {
                $balance = 0;
            }

            if ($balance < 0.1) {
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance'
                ]);
            }

            try {
                $url = $rpcUrl . 'transactions/sign';
                $params = array(
                    'type' => 4,
                    'sender' => $address,
                    'recipient' => $adminAddress->value,
                    'amount' => ($balance - 0.1) / $this::CLB_UNIT,
                    'assetId' => $this::CLB_ASSETID,
                    'feeAssetId' => $this::CLB_ASSETID,
                    'fee' => 10000000,
                    'attachment' => ''
                );

                $result = $util->curl_post_forwaves($url, $params);

                if (!isset($result->id)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Please try again'
                    ]);
                }

                $result = $util->curl_post($rpcUrl.'transactions/broadcast', $result);

                if (isset($result->id) && $result->id) {
                    $move = new MoveHistory();
                    $move->coin = 'CLB';
                    $move->datetime = Carbon::now();
                    $move->txid = $result->id;
                    $move->fee = 0.1;
                    $move->from = $address;
                    $move->to = $adminAddress->value;
                    $move->amount = $balance;
                    $move->save();
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }
            }  catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'ADMN') {
            $adminAddress = AdminSetting::where('name', 'waves_address')->first();

            $rpcUrl = env('WAVES_RPC_URL');

            $balance = 0;

            $util = new Utility();

            $url = $rpcUrl.'assets/balance/'.$address.'/'.$this::ADMN_ASSETID;

            try {
                $result = $util->curl_get_contents($url);
                if (isset($result->balance)) {
                    $balance = $result->balance * $this::WAVES_UNIT;
                }
            } catch (\Exception $e) {
                $balance = 0;
            }

            if ($balance < 0.1) {
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance'
                ]);
            }

            try {
                $url = $rpcUrl . 'transactions/sign';
                $params = array(
                    'type' => 4,
                    'sender' => $address,
                    'recipient' => $adminAddress->value,
                    'amount' => ($balance - 0.1) / $this::ADMN_UNIT,
                    'assetId' => $this::ADMN_ASSETID,
                    'feeAssetId' => $this::ADMN_ASSETID,
                    'fee' => 10000000,
                    'attachment' => ''
                );

                $result = $util->curl_post_forwaves($url, $params);

                if (!isset($result->id)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Please try again'
                    ]);
                }

                $result = $util->curl_post($rpcUrl.'transactions/broadcast', $result);

                if (isset($result->id) && $result->id) {
                    $move = new MoveHistory();
                    $move->coin = 'ADMN';
                    $move->datetime = Carbon::now();
                    $move->txid = $result->id;
                    $move->fee = 0.1;
                    $move->from = $address;
                    $move->to = $adminAddress->value;
                    $move->amount = $balance;
                    $move->save();
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => isset($result->Error) ? $result->Error : 'Please try again'
                    ]);
                }
            }  catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'ONT') {
            $ontSetting = ONTSetting::pluck('value', 'name');

            $rpcUrl = env('ONT_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $cliUrl = env('ONT_CLI_RPC_URL');

            $utility = new Utility();

            $balance = 0;
            $ong_balance = 0;

            try {
                $result = $jsonRpc->getbalance($address);
                if (isset($result['ont'])) {
                    $balance = $result['ont'];
                    $ong_balance = $result['ong'];
                }
            } catch (\Exception $e) {
                $balance = 0;
            }

            if ($ong_balance < 10000000) {
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
                                        $ontSetting['admin_address'], $address, '10000000'
                                    )
                                )
                            )
                        )
                    ));

                    $result = $jsonRpc->sendrawtransaction($result['signed_tx']);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Low Balance'
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance, try again 1 min later'
                ]);
            }

            $ontAddress = ONTAddress::where('address', $address)->first();
            if (!$ontAddress) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }
            try {
                $result = $utility->ont_sigsvr_post($cliUrl, array(
                    'qid' => 't',
                    'method' => 'signativeinvoketx',
                    'account' => $address,
                    'pwd' => $ontAddress->password,
                    'params' => array(
                        'gas_price' => 500,
                        'gas_limit' => 20000,
                        'address' => $this::ONT_CONTRACT_ADDRESS,
                        'method' => 'transfer',
                        'version' => 0,
                        'params' => array(
                            array(
                                array(
                                    $address, $ontSetting['admin_address'], number_format(round($balance, 0), 0, '.', ''),
                                )
                            )
                        )
                    )
                ));

                $result = $jsonRpc->sendrawtransaction($result['signed_tx']);
                \Log::info($result);

                $move = new MoveHistory();
                $move->coin = 'ONT';
                $move->datetime = Carbon::now();
                $move->txid = $result;
                $move->from = $address;
                $move->to = $ontSetting['admin_address'];
                $move->amount = $balance;
                $move->fee = 0.01;
                $move->save();
            } catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        } else if ($coin == 'ONG') {
            $ontSetting = ONTSetting::pluck('value', 'name');

            $rpcUrl = env('ONT_RPC_URL');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $cliUrl = env('ONT_CLI_RPC_URL');

            $utility = new Utility();

            $balance = 0;

            try {
                $result = $jsonRpc->getbalance($address);
                if (isset($result['ong'])) {
                    $balance = $result['ong'];
                }
            } catch (\Exception $e) {
                $balance = 0;
            }

            if ($balance < 100000000) {
                return response()->json([
                    'success' => false,
                    'error' => 'Low Balance'
                ]);
            }

            $ontAddress = ONTAddress::where('address', $address)->first();
            if (!$ontAddress) {
                return response()->json([
                    'success' => false,
                    'error' => 'No address'
                ]);
            }
            try {
                $result = $utility->ont_sigsvr_post($cliUrl, array(
                    'qid' => 't',
                    'method' => 'signativeinvoketx',
                    'account' => $address,
                    'pwd' => $ontAddress->password,
                    'params' => array(
                        'gas_price' => 500,
                        'gas_limit' => 20000,
                        'address' => $this::ONG_CONTRACT_ADDRESS,
                        'method' => 'transfer',
                        'version' => 0,
                        'params' => array(
                            array(
                                array(
                                    $address, $ontSetting['admin_address'], number_format(round($balance - 100000000, 0), 0, '.', ''),
                                )
                            )
                        )
                    )
                ));

                $result = $jsonRpc->sendrawtransaction($result['signed_tx']);

                $move = new MoveHistory();
                $move->coin = 'ONG';
                $move->datetime = Carbon::now();
                $move->txid = $result;
                $move->from = $address;
                $move->to = $ontSetting['admin_address'];
                $move->amount = $balance;
                $move->fee = 0.01;
                $move->save();
            } catch (\Exception $e) {
                return response($e->getMessage(), 403);
            }

            return response()->json([
                'success' => true
            ]);
        }
    }
}
