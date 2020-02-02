<?php

namespace App\Http\Controllers\Api;

use App\Entities\AchainSetting;
use App\Entities\BenSetting;
use App\Entities\BitcoenAddress;
use App\Entities\BtwSetting;
use App\Entities\CLOAddress;
use App\Entities\CLOPending;
use App\Entities\CLOSetting;
use App\Entities\Coin;
use App\Entities\CoinAddress;
use App\Entities\CoinScanSetting;
use App\Entities\CoinScanStatus;
use App\Entities\CoinWithdrawHistory;
use App\Entities\ConcealSetting;
use App\Entities\EthereumAddress;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\EthereumBalance;
use App\Entities\EthereumMove;
use App\Entities\EthereumPending;
use App\Entities\EthereumSetting;
use App\Entities\EtnSetting;
use App\Entities\IEOCoinPair;
use App\Entities\MPGSetting;
use App\Entities\ScorumSetting;
use App\Entities\Transaction;
use App\Entities\TronAddress;
use App\Entities\TronSetting;
use App\Entities\XSNInvalidTransaction;
use App\Entities\ZecAddress;
use App\Libs\BitcoenRPC;
use App\Libs\JsonRPCClient;
use App\Http\Controllers\Controller;
use App\Libs\SMS;
use App\Libs\Utility;
use App\User;
use Carbon\Carbon;
use Hamcrest\Util;
use Illuminate\Http\Request;

class TestController extends Controller
{
    const ETH_UNIT = 1 / 1000000000000000000;
    const ERC20_TRANSFER_HEX = '0xa9059cbb';
    const ERC20_BALANCEOF_HEX = '0x70a08231';

    const TRX_UNIT = 1 / 1000000;
    const TRC20_TRANSFER_HEX = 'a9059cbb';
    const TRC20_DECIMALS = array(
        'IGG' => 6
    );
    const TRC10_DECIMALS = array(
        'BTT' => 6
    );

    const XRP_UNIT = 1 / 1000000;
    const XRP_INIT_TIME = 946684800;

    const XMR_UNIT = 1 / 1000000000000;

    const XIN_UNIT = 1 / 100000000;
    const XIN_INIT_TIME = 1484020800;

    const USDT_PROPERTY = 31;

    const BTW_UNIT = 1 / 100000000;
    const BTW_FIRSTTIME = 1467057600;

    const BTS_UNIT = 1 / 100000;
    const CVN_UNIT = 1 / 10000;
    const CVN_ASSETID = '1.3.1473';
    const BTS_ACCOUNT_NAME = 'sk-bitshares';
    const BTS_ACCOUNT_ID = '1.2.981431';
    const BTS_WALLET_PWD = 'OIjoiljcvoijsodfjoaisdjfoahwefiuschvihasoi2938r4uoisdfjkasf';
    const DEEX_UNIT = 1 / 10000;
    const DEEX_ASSETID = '1.3.2230';

    const ACT_UNIT = 1 / 100000;
    const VEX_UNIT = 1 / 100000;
    const VEX_CONTRACT = 'CON9XnhX5FtQqGFAa3KgrgkPCCEDPmuzgtSx';

    const PIRL_UNIT = 1 / 1000000000000000000;

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
        'EYC' => 12,
        'AGVC' => 18,
        'HKN' => 8,
        'FORK' => 18,
        'MNC' => 18,
        'LAAR' => 18,
        'ANKR' => 18,
        'W12' => 18,
        'CBEX' => 18,
        'UPC' => 8
    );

    const CLB_UNIT = 1 / 100000000;
    const WAVES_UNIT = 1 / 100000000;
    const CLB_ASSETID = 'CfMBo6GAZZhXs3bbS64NwAor24CuXwYVGJ34SSkPKHVf';

    const ETN_UNIT = 1 / 100;

    const ONG_UNIT = 1 / 1000000000;
    const ONT_UNIT = 1;
    const ONG_CONTRACT_ADDRESS = '0200000000000000000000000000000000000000';
    const ONT_CONTRACT_ADDRESS = '0100000000000000000000000000000000000000';

    const MPG_INIT_TIME = 1514761200;
    const MPG_UNIT = 1 / 100000000;

    public function etherScan() {
        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $accounts = CoinAddress::whereNotNull('eth_address')->pluck('user_id', 'eth_address');

        $current_block = 7690129;
        $max_block = 7690129;

        $walletController = new WalletController();

        $coinSettings = Coin::pluck('deposit', 'coin');

        for ($i = $current_block; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->eth_getBlockByNumber('0x' . dechex($i), true);
            } catch (\Exception $e) {
                return 'false';
            }

            if (count($result['transactions']) > 0) {
                foreach ($result['transactions'] as $tx) {
                    if (isset($accounts[$tx['to']]) && $accounts[$tx['to']] != '' && $coinSettings['ETH'] == 1) {

                        try {
                            $receipt_tx = $jsonRpc->eth_getTransactionReceipt($tx['hash']);
                        } catch (\Exception $e) {
                            continue;
                        }

                        if ($receipt_tx['status'] == '0x0') continue;

                        $move = EthereumMove::where('txid', $tx['hash'])->first();

                        if ($move) {
                            if ($walletController->checkErc20Address($move->coin, $move->to)) {
                                $move->moved = 1;
                                $move->save();
                            }
                            continue;
                        }

                        $oldTx = Transaction::where([['dest_coin', 'ETH'], ['user_id', $accounts[$tx['to']]], ['txid', $tx['hash']]])->first();

                        if (!$oldTx) {
                            var_dump($tx['hash']);
                            $ethTransaction = new Transaction();

                            $ethTransaction->user_id = $accounts[$tx['to']];
                            $ethTransaction->datetime = date("Y-m-d H:i:s", hexdec($result['timestamp']));
                            $ethTransaction->dest_coin = 'ETH';
                            $ethTransaction->dest_amount = hexdec($tx['value']) * $this::ETH_UNIT;
                            $ethTransaction->type = TransactionType::DEPOSIT;
                            $ethTransaction->status = TransactionStatus::SUCCESS;
                            $ethTransaction->address = $tx['from'];
                            $ethTransaction->txid = $tx['hash'];
                            $ethTransaction->save();

                            $walletController->checkETHAddress($tx['to']);
                        }
                    }
                    foreach ($this::ERC20_DECIMALS as $coin => $decimal) {

                        if (strtolower($tx['to']) == strtolower(env($coin . '_CONTRACT_ADDRESS'))  && isset($coinSettings[$coin]) && $coinSettings[$coin] == 1) {
                            $data = $tx['input'];

                            $method = substr($data, 0, 10);
                            $to = '0x' . substr($data, 34, 40);
                            // $to = '0x'.ltrim(substr($data, 10, 64), '0');
                            $value = '0x' . ltrim(substr($data, -64), '0');

                            if ($method == $this::ERC20_TRANSFER_HEX && isset($accounts[$to]) && $accounts[$to] != '') {
                                try {
                                    $receipt_tx = $jsonRpc->eth_getTransactionReceipt($tx['hash']);
                                } catch (\Exception $e) {
                                    continue;
                                }

                                if ($receipt_tx['status'] == '0x0') continue;
                                if (count($receipt_tx['logs']) == 0) continue;

                                $oldTx = Transaction::where([['dest_coin', $coin], ['user_id', $accounts[$to]], ['txid', $tx['hash']]])->first();

                                if (!$oldTx) {
                                    var_dump($tx);
                                    $tran = new Transaction();

                                    $tran->user_id = $accounts[$to];
                                    $tran->datetime = date("Y-m-d H:i:s", hexdec($result['timestamp']));
                                    $tran->dest_coin = $coin;
                                    $tran->dest_amount = hexdec($value) / pow(10, $decimal);
                                    $tran->type = TransactionType::DEPOSIT;
                                    $tran->status = TransactionStatus::SUCCESS;
                                    $tran->address = $tx['from'];
                                    $tran->txid = $tx['hash'];
                                    $tran->save();

                                    $walletController->checkErc20Address($coin, $to);
                                }
                            }
                        }
                    }
                }
            }

            // \Log::info($i.":".count($result['transactions']));

            //$ethereumSetting->value = '0x' . dechex($i);
            //$ethereumSetting->save();
        }

    }

    public function achainScan() {
        $rpcUrl = env('ACHAIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $current_block = 3203350;

        $accounts = CoinAddress::whereNotNull('act_address')->pluck('user_id', 'act_address');

        for ($i = $current_block; $i <= $current_block; $i++) {
            try {
                $result = $jsonRpc->blockchain_get_block($i);
            } catch (\Exception $e) {
                return 'false';
            }

            if (count($result['user_transaction_ids']) > 0) {
                var_dump($result['user_transaction_ids']);
                foreach ($result['user_transaction_ids'] as $txid) {
                    try {
                        $txresult = $jsonRpc->blockchain_get_transaction($txid);
                    } catch (\Exception $e) {
                        continue;
                    }

                    if (isset($txresult[1]['trx']['alp_account']) && $txresult[1]['trx']['alp_account'] != '' && isset($accounts[$txresult[1]['trx']['alp_account']])) {
                        foreach ($txresult[1]['trx']['operations'] as $rec) {
                            if ($rec['type'] == 'deposit_op_type') {
                                $oldTx = Transaction::where([['dest_coin', 'ACT'], ['user_id', $accounts[$txresult[1]['trx']['alp_account']]], ['txid', $txid]])->first();

                                if (!$oldTx) {
                                    $tran = new Transaction();

                                    $tran->user_id = $accounts[$txresult[1]['trx']['alp_account']];
                                    $tran->dest_coin = 'ACT';
                                    $tran->dest_amount = $rec['data']['amount'] * $this::ACT_UNIT;
                                    $tran->type = TransactionType::DEPOSIT;
                                    $tran->status = TransactionStatus::SUCCESS;
                                    $tran->txid = $txid;

                                    try {
                                        $txdetail = $jsonRpc->blockchain_get_pretty_transaction($txid);
                                    } catch (\Exception $e) {
                                        continue;
                                    }
                                    $tran->datetime = $txdetail['timestamp'];
                                    $tran->address = $txdetail['ledger_entries'][0]['from_account'];

                                    $tran->save();
                                }
                            }
                        }
                    } else if (isset($txresult[1]['trx']['operations'][0]['type']) && $txresult[1]['trx']['operations'][0]['type'] == 'transaction_op_type') {
                        var_dump($txid);
                        try {
                            $eventdetail = $jsonRpc->blockchain_get_events($i, $txid);
                        } catch (\Exception $e) {
                            continue;
                        }

                        if ($eventdetail[0]['event_type'] != 'transfer_to_success') continue;

                        $params = explode(',', $eventdetail[0]['event_param']);

                        $balance = isset($params[1]) ? floatval(explode(':', $params[1])[1]) : 0;

                        try {
                            $condetail = $jsonRpc->blockchain_get_pretty_contract_transaction($txid);
                        } catch (\Exception $e) {
                            continue;
                        }


                        $reserved = explode('|', $condetail['reserved'][1]);
                        if (isset($condetail['to_contract_ledger_entry']) && $condetail['to_contract_ledger_entry']['to_account'] == $this::VEX_CONTRACT
                            && isset($reserved[0]) && isset($accounts[$reserved[0]])) {
                            $oldTx = Transaction::where([['dest_coin', 'VEX'], ['txid', $condetail['orig_trx_id']]])->first();

                            if (!$oldTx) {
                                $tran = new Transaction();

                                $tran->user_id = $accounts[$reserved[0]];
                                $tran->dest_coin = 'VEX';
                                $tran->dest_amount = isset($reserved[1]) ? floatval($reserved[1]) : 0;
                                $tran->type = TransactionType::DEPOSIT;
                                $tran->status = TransactionStatus::SUCCESS;
                                $tran->txid = $condetail['orig_trx_id'];
                                $tran->datetime = $condetail['timestamp'];
                                $tran->address = $condetail['to_contract_ledger_entry']['from_account'];

                                $tran->save();
                                var_dump($condetail['orig_trx_id']);

//                                $vexbalance = AchainSetting::where('name', 'vex_balance')->first();
//                                $vexbalance->value = round($balance * $this::ACT_UNIT, 5);
//                                $vexbalance->save();
                            }
                        }
                    }
                }
            }
        }
    }

    public function CLOScan()
    {
        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $accounts = CLOAddress::whereNotNull('address')->pluck('user_id', 'address');

        $current_block = 1106270;
        $max_block = $current_block;

        $walletController = new WalletController();

        for ($i = $current_block; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->eth_getBlockByNumber('0x' . dechex($i), true);
            } catch (\Exception $e) {
                return $e->getMessage();
            }

            if (count($result['transactions']) > 0) {
                foreach ($result['transactions'] as $tx) {
                    if (isset($accounts[$tx['to']]) && $accounts[$tx['to']] != '') {

                        try {
                            $receipt_tx = $jsonRpc->eth_getTransactionReceipt($tx['hash']);
                        } catch (\Exception $e) {
                            continue;
                        }

                        if ($receipt_tx['status'] == '0x0') continue;

                        $oldTx = Transaction::where([['dest_coin', 'CLO'], ['user_id', $accounts[$tx['to']]], ['txid', $tx['hash']]])->first();

                        if (!$oldTx) {
                            $ethTransaction = new Transaction();

                            $ethTransaction->user_id = $accounts[$tx['to']];
                            $ethTransaction->datetime = date("Y-m-d H:i:s", hexdec($result['timestamp']));
                            $ethTransaction->dest_coin = 'CLO';
                            $ethTransaction->dest_amount = hexdec($tx['value']) * $this::ETH_UNIT;
                            $ethTransaction->type = TransactionType::DEPOSIT;
                            $ethTransaction->status = TransactionStatus::SUCCESS;
                            $ethTransaction->address = $tx['from'];
                            $ethTransaction->txid = $tx['hash'];
                            $ethTransaction->save();

                            $walletController->checkCLOAddress($tx['to']);
                        }
                    }
                }
            }
        }
    }

    public function pirlScan()
    {
        $rpcUrl = env('PIRL_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $current_block = '0x1e5011';
        $result = '0x1e5011';

        $accounts = CoinAddress::whereNotNull('pirl_address')->pluck('user_id', 'pirl_address');

        $current_block = hexdec($current_block);
        $max_block = hexdec($result);

        for ($i = $current_block; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->eth_getBlockByNumber('0x' . dechex($i), true);
            } catch (\Exception $e) {
                return 'false';
            }

            if (count($result['transactions']) > 0) {
                foreach ($result['transactions'] as $tx) {
                    if (isset($accounts[$tx['to']]) && $accounts[$tx['to']] != '') {
                        try {
                            $receipt_tx = $jsonRpc->eth_getTransactionReceipt($tx['hash']);
                        } catch (\Exception $e) {
                            continue;
                        }

                        if (isset($receipt_tx['status']) && $receipt_tx['status'] == '0x0') continue;

                        $oldTx = Transaction::where([['dest_coin', 'PIRL'], ['user_id', $accounts[$tx['to']]], ['txid', $tx['hash']]])->first();

                        if (!$oldTx) {
                            $pirlTransaction = new Transaction();

                            $pirlTransaction->user_id = $accounts[$tx['to']];
                            $pirlTransaction->datetime = date("Y-m-d H:i:s", hexdec($result['timestamp']));
                            $pirlTransaction->dest_coin = 'PIRL';
                            $pirlTransaction->dest_amount = hexdec($tx['value']) * $this::PIRL_UNIT;
                            $pirlTransaction->type = TransactionType::DEPOSIT;
                            $pirlTransaction->status = TransactionStatus::SUCCESS;
                            $pirlTransaction->address = $tx['from'];
                            $pirlTransaction->txid = $tx['hash'];
                            $pirlTransaction->save();

                            var_dump($tx['hash']);
                        }
                    }
                }
            }
        }
    }

    public function WAVESScan() {
        $rpcUrl = env('WAVES_RPC_URL');

        $util = new Utility();

        $block = 1202744;

        $accounts = CoinAddress::whereNotNull('waves_address')->pluck('user_id', 'waves_address');

        for ($block=1218944; $block <= 1218944; $block ++) {
            $url = $rpcUrl . 'blocks/at/' . $block;

            \Log::info($block);
            try {
                $result = $util->curl_get_contents($url);
            } catch (\Exception $e) {
                return;
            }

            if (isset($result->status) && $result->status == 'error') {
                return;
            }

            if (isset($result->transactions)) {
                foreach ($result->transactions as $tx) {
                    if (isset($tx->type) && $tx->type == 4 && isset($tx->sender) && isset($accounts[$tx->recipient])) {

                        if (is_null($tx->assetId)) {
                            $oldTx = Transaction::where([['dest_coin', 'WAVES'], ['user_id', $accounts[$tx->recipient]], ['txid', $tx->id]])->first();

                            if (!$oldTx) {
                                $trxTransaction = new Transaction();

                                $trxTransaction->user_id = $accounts[$tx->recipient];
                                $trxTransaction->datetime = date("Y-m-d H:i:s", intval($tx->timestamp / 1000));
                                $trxTransaction->dest_coin = 'WAVES';
                                $trxTransaction->dest_amount = $tx->amount * $this::WAVES_UNIT;
                                $trxTransaction->type = TransactionType::DEPOSIT;
                                $trxTransaction->status = TransactionStatus::SUCCESS;
                                $trxTransaction->address = $tx->sender;
                                $trxTransaction->txid = $tx->id;
                                $trxTransaction->save();
                            }
                        } else if ($tx->assetId == $this::CLB_ASSETID) {
                            $oldTx = Transaction::where([['dest_coin', 'CLB'], ['user_id', $accounts[$tx->recipient]], ['txid', $tx->id]])->first();

                            if (!$oldTx) {
                                $trxTransaction = new Transaction();

                                $trxTransaction->user_id = $accounts[$tx->recipient];
                                $trxTransaction->datetime = date("Y-m-d H:i:s", intval($tx->timestamp / 1000));
                                $trxTransaction->dest_coin = 'CLB';
                                $trxTransaction->dest_amount = $tx->amount * $this::CLB_UNIT;
                                $trxTransaction->type = TransactionType::DEPOSIT;
                                $trxTransaction->status = TransactionStatus::SUCCESS;
                                $trxTransaction->address = $tx->sender;
                                $trxTransaction->txid = $tx->id;
                                $trxTransaction->save();
                            }
                        }
                    }
                }
            }
        }
    }

    public function ethWithdrawTest(Request $request) {

        $addresses = CoinAddress::whereNotNull('btw_address')->pluck('user_id', 'btw_address');

        $btwSetting = BtwSetting::find(1);

        $util = new Utility();

        $rpcUrl = env('BITWHITE_RPC_URL');
        $url = $rpcUrl."blocks/getheight";

        $result = $util->curl_get_contents($url);

        $maxBlock = 0;
        if (isset($result->success) && $result->success) {
            $maxBlock = $result->height;
        }

        var_dump($maxBlock);
        var_dump($btwSetting->value + 1);

        $maxBlock = 718186;
        for ($i = $maxBlock; $i <= $maxBlock; $i ++) {
            $url = $rpcUrl."blocks/get?height=".$i;

            $result = $util->curl_get_contents($url);
            $blockId = '';
            if (isset($result->success) && $result->success) {
                $blockId = $result->block->id;
            }

            var_dump($blockId);

            if (!empty($blockId)) {
                $url = $rpcUrl."transactions?blockId=".$blockId;
                $result = $util->curl_get_contents($url);

                if (isset($result->success) && $result->success) {
                    foreach ($result->transactions as $tx) {
                        if (isset($addresses[$tx->recipientId])) {
                            $oldTx = Transaction::where([['user_id', $addresses[$tx->recipientId]], ['txid', $tx->id], ['type', TransactionType::DEPOSIT], ['dest_coin', 'BTW']])->first();

                            if (!$oldTx) {
                                $tran = new Transaction();

                                $tran->user_id = $addresses[$tx->recipientId];
                                $tran->datetime = date("Y-m-d H:i:s", $tx->timestamp + $this::BTW_FIRSTTIME);
                                $tran->dest_coin = 'BTW';
                                $tran->dest_amount = $tx->amount * $this::BTW_UNIT;
                                $tran->type = TransactionType::DEPOSIT;
                                $tran->status = TransactionStatus::SUCCESS;
                                $tran->address = $tx->senderId;
                                $tran->txid = $tx->id;
                                $tran->save();
                            }
                        }
                    }
                }
            }
        }

        $controller = new WalletController();

//        $controller->checkBTCBalance();
//        return response()->json([
//            'success' => true,
//        ]);

        // var_dump(bcrypt('sistem123456'));
        //$depositController = new DepositController();
        //$depositController->getETHDeposit();

        // $controller->checkCLOAddress('0x1718dbb7e1377db9836b7e836c40bfd48f8829ef');

//        $utility = new Utility();
//
//        $addresses = array(
//            '0x7d58d34fb93f60bcbbf7c850e844e3c41c2c7154',
//            //'0xf6224249c0f962f1616d8115949e721aa2aebc43'
//        );
//
//        $coin = 'QBIT';
//        foreach ($addresses as $address) {
//            var_dump($controller->checkErc20Address($coin, $address, true));
//            //var_dump($address);
//            //$controller->checkETHAddress($address);
//        }
//
//        $total = 0;
//        $addresses = EthereumAddress::all();
//        foreach ($addresses as $address) {
//            $balance = $controller->getETHBalance($address->address);
//
//            if ($balance > 0.1) {
//                var_dump($address->address.':'.$balance.'<br>');
//            }
//            //$controller->checkETHAddress($address->address);
//        }
//
        // $rpcUrl = env('ETHEREUM_ADMIN_RPC_URL');
//        $rpcUrl = env('ETHEREUM_RPC_URL');
//
//        $jsonRpc = new JsonRPCClient($rpcUrl);
//
//        $addresses = array(
//        );
//
//        foreach ($addresses as $address) {
//            $ethAddress = EthereumAddress::where('address', $address)->first();
//            if (!$ethAddress) continue;
//
//            $balance = $controller->getETHBalance($address);
//
//            $from = $address;
//            $coin = 'ETH';
//            $amount = $balance - 0.001;
//            $to = '0x5bcbf9f3ef9521a548e7e8b697ecfe29fc2933e1';
//
//            $utility = new Utility();
//
//            $amountHex = $utility->convertHex($amount, 18);
//
//            $data = $this::ERC20_TRANSFER_HEX . str_pad(substr($to, 2), 64, "0", STR_PAD_LEFT) . str_pad($amountHex, 64, "0", STR_PAD_LEFT);
//
//            $result = '';
//            try {
//                $gasPrice = $jsonRpc->eth_gasPrice();
//
//                //            $estimateGas = $jsonRpc->eth_estimateGas(array(
//                //                'from' => $from,
//                //                'to' => env($coin . '_CONTRACT_ADDRESS'),
//                //                'value' => '0x0',
//                //                'data' => $data,
//                //                'gasPrice' => '0x' . dechex(hexdec($gasPrice) * 2),
//                //            ));
//                //
//                //            $result = $jsonRpc->personal_sendTransaction(array(
//                //                'from' => $from,
//                //                'to' => env($coin . '_CONTRACT_ADDRESS'),
//                //                'value' => '0x0',
//                //                'data' => $data,
//                //                // 'nonce' => $nonce,
//                //                'gas' => $estimateGas,
//                //                'gasPrice' => '0x' . dechex(hexdec($gasPrice) * 2),
//                //            ), 'bzR2j1wY8HkTvjujvV7UqhPek7nDdVgBXcAmg4C2zfdVuEANSR');
//
//                $result = $jsonRpc->personal_sendTransaction(array(
//                    'from' => $from,
//                    'to' => $to,
//                    'value' => '0x' . $amountHex,
//                    // 'nonce' => $nonce,
//                    'gasPrice' => '0x' . dechex(hexdec($gasPrice) * 2)
//                ), $ethAddress->secret);
//                var_dump($result);
//
//            } catch (\Exception $e) {
//                var_dump($e->getMessage());
//                return response()->json([
//                    'success' => false,
//                ]);
//            }
//        }

        //        $tx = Transaction::where('txid', $oldtxid)->first();
        //        if ($tx) {
        //            $tx->txid = $result;
        //            $tx->save();
        //        }
        //
        //        $move = EthereumMove::where('txid', $oldtxid)->first();
        //        if ($move) {
        //            $move->txid = $result;
        //            $move->save();
        //        }
        //
        //        $pendings = EthereumPending::where('txid', $oldtxid)->get();
        //        foreach ($pendings as $pending) {
        //            $pending->delete();
        //        }

//        $pending = new EthereumPending();
//        $pending->from = $from;
//        $pending->to = $to;
//        $pending->amount = $amount;
//        $pending->txid = $result;
//        $pending->coin = $coin;
//
//        $pending->save();

//        var_dump($result);
        return response()->json([
            'success' => true,
        ]);

    }

    public function erc20WithdrawTest(Request $request) {

    }

    public function getAllBalance(Request $request) {
        $coin = $request->input('coin');
        $trans = Transaction::where([['src_coin', $coin], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->selectRaw('transactions.id,user_id,src_amount,fee,is_epay,address,destination_tag,transactions.created_at,users.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);  
    }

    public function getAllErc20Balance(Request $request) {
        $coin = $request->input('coin');
        $trans = Transaction::where([['src_coin', $coin], ['type', TransactionType::WITHDRAW], ['status', TransactionStatus::PENDING]])
            ->leftJoin('users', 'users.id', 'transactions.user_id')
            ->selectRaw('transactions.*,users.name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trans
        ]);
    }

    public function checkCoin($coin, $address) {

        $controller = new WalletController();

        if ($coin == 'ETH') {
            $controller->checkETHAddress($address);
        } else {
            $controller->checkErc20Address($coin, $address, true);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function checkBZX() {
        $rpcUrl = env('BZX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $count = 0;
        $num = 10;

        $users = User::all()->pluck('name', 'email');

        $accounts = User::pluck('user_id', 'email');

        while(true) {
            try {
                $result = $jsonRpc->listtransactions("*", $num, $num * $count);

                if (count($result) > 0) {
                    foreach ($result as $tran) {
                        if ($tran['category'] == 'receive' && $tran['confirmations'] == 0) {
                            $oldTx = Transaction::where([['dest_coin', 'BZX'], ['user_id', $accounts[$tran['account']]], ['txid', $tran['txid']]])->first();

                            if (!$oldTx) {
                                $bzxTran = new Transaction();

                                $bzxTran->user_id = $accounts[$tran['account']];
                                $bzxTran->datetime = date("Y-m-d H:i:s", $tran['time'] * 1000);
                                $bzxTran->dest_coin = 'BZX';
                                $bzxTran->dest_amount = $tran['amount'];
                                $bzxTran->type = TransactionType::DEPOSIT;
                                $bzxTran->status = TransactionStatus::SUCCESS;
                                $bzxTran->txid = $tran['txid'];
                                $bzxTran->save();

                                var_dump($tran['txid']);
                            }
                        }
                    }
                } else {
                    break;
                }
                $count ++;
            } catch (\Exception $e) {
                break;
            }
        }
    }

    public function ETNScan()
    {
        $rpcUrl = env('ETN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        $addresses = CoinAddress::whereNotNull('etn_paymentid')->pluck('user_id', 'etn_paymentid');

        $amount = 0;
        try {
            $result = array();

            $result = $jsonRpc->get_transfers(array(
                'in' => true,
                'filter_by_height' => true,
                'min_height' => 440075,
                'max_height' => 440077
            ));

            if ($result['in']) {
                foreach ($result['in'] as $data) {
                    if (isset($addresses[$data['payment_id']])) {
                        $oldTx = Transaction::where([['dest_coin', 'ETN'], ['user_id', $addresses[$data['payment_id']]], ['txid', $data['txid']]])->first();

                        if (!$oldTx) {
                            $ethTransaction = new Transaction();

                            $ethTransaction->user_id = $addresses[$data['payment_id']];
                            $ethTransaction->datetime = date("Y-m-d H:i:s", $data['timestamp']);
                            $ethTransaction->dest_coin = 'ETN';
                            $ethTransaction->dest_amount = $data['amount'] * $this::ETN_UNIT;
                            $ethTransaction->type = TransactionType::DEPOSIT;
                            $ethTransaction->status = TransactionStatus::PENDING;
                            //$ethTransaction->address = $tx['from'];
                            $ethTransaction->txid = $data['txid'];
                            $ethTransaction->save();

                        }
                    }
                }
            }

        } catch (\Exception $e) {
        }

        var_dump('end');
    }


    public function scorumScan() {
        $status = CoinScanStatus::where('coin', 'SCR')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('SCR_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $scrBlockSetting = ScorumSetting::where('name', 'scr_block')->first();
        $scrOpSetting = ScorumSetting::where('name', 'scr_opid')->first();

        $current_block = isset($scrBlockSetting->value) ? $scrBlockSetting->value : 0;
        $current_op = isset($scrOpSetting->value) ? $scrOpSetting->value : 0;

        $accounts = CoinAddress::whereNotNull('scr_address')->pluck('user_id', 'scr_address');

        try {
            $rs = $jsonRpc->unlock('14753698');
            $result = $jsonRpc->get_account_history('sistemkoin-scr', -1, 20);
            $rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            var_dump($e->getMessage());
            return false;
        }

        $txs = array();
        foreach ($result as $tx) {
            $op = $tx[1]['op'];
            $opid = $tx[0];
            $blocknum = $tx[1]['block'];

            if ($opid <= $current_op || $blocknum < $current_block) continue;
            if ($op[0] != 'transfer') continue;

            if ($op[1]['to'] != 'sistemkoin-scr')
                continue;

            if (substr($op[1]['amount'], -3) == 'SCR') {
                if (isset($op[1]['memo']) && isset($accounts[$op[1]['memo']]) && $accounts[$op[1]['memo']] != '') {
                    $oldTx = Transaction::where([['dest_coin', 'SCR'], ['user_id', $accounts[$op[1]['memo']]], ['txid', $tx[1]['trx_id']]])->first();

                    if (!$oldTx) {
                        $scrTransaction = new Transaction();

                        $scrTransaction->user_id = $accounts[$op[1]['memo']];
                        $scrTransaction->datetime = date("Y-m-d H:i:s");
                        $scrTransaction->dest_coin = 'SCR';
                        $scrTransaction->dest_amount = floatval($op[1]['amount']);
                        $scrTransaction->type = TransactionType::DEPOSIT;
                        $scrTransaction->status = TransactionStatus::SUCCESS;
                        $scrTransaction->address = $op[1]['from'];
                        $scrTransaction->txid = $tx[1]['trx_id'];
                        $scrTransaction->save();
                    } else {
                        continue;
                    }
                }
            }

            $scrBlockSetting->value = $blocknum;
            $scrBlockSetting->save();

            $scrOpSetting->value = $opid;
            $scrOpSetting->save();
        }
        $status->status = 0;
        $status->save();
    }

    public function ontScanTest() {

        $rpcUrl = env('ONT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $i = 1670631;
        try {
            $txs = $jsonRpc->getsmartcodeevent($i);
        } catch (\Exception $e) {
            return 'false';
        }

        $accounts = CoinAddress::whereNotNull('ont_address')->pluck('user_id', 'ont_address');

        if (count($txs) > 0) {
            foreach ($txs as $tx) {
                if ($tx['State'] == 1 && count($tx['Notify']) > 0) {
                    foreach ($tx['Notify'] as $detail) {
                        var_dump($detail);
                        if ($detail['ContractAddress'] == $this::ONT_CONTRACT_ADDRESS
                            && isset($detail['States'][0]) && $detail['States'][0] == 'transfer'
                            && isset($detail['States'][2]) && isset($accounts[$detail['States'][2]])) {

                            $oldTx = Transaction::where([['dest_coin', 'ONT'], ['user_id', $accounts[$detail['States'][2]]], ['txid', $tx['TxHash']]])->first();

                            if (!$oldTx) {
                                $ontTransaction = new Transaction();

                                $ontTransaction->user_id = $accounts[$detail['States'][2]];
                                $ontTransaction->datetime = date("Y-m-d H:i:s");
                                $ontTransaction->dest_coin = 'ONT';
                                $ontTransaction->dest_amount = $detail['States'][3] * $this::ONT_UNIT;
                                $ontTransaction->type = TransactionType::DEPOSIT;
                                $ontTransaction->status = TransactionStatus::SUCCESS;
                                $ontTransaction->address = $detail['States'][1];
                                $ontTransaction->txid = $tx['TxHash'];
                                $ontTransaction->save();
                            }
                        } else if ($detail['ContractAddress'] == $this::ONG_CONTRACT_ADDRESS
                            && isset($detail['States'][0]) && $detail['States'][0] == 'transfer'
                            && isset($detail['States'][2]) && isset($accounts[$detail['States'][2]])) {

                            $oldTx = Transaction::where([['dest_coin', 'ONG'], ['user_id', $accounts[$detail['States'][2]]], ['txid', $tx['TxHash']]])->first();

                            if (!$oldTx) {
                                $ontTransaction = new Transaction();

                                $ontTransaction->user_id = $accounts[$detail['States'][2]];
                                $ontTransaction->datetime = date("Y-m-d H:i:s");
                                $ontTransaction->dest_coin = 'ONG';
                                $ontTransaction->dest_amount = $detail['States'][3] * $this::ONG_UNIT;
                                $ontTransaction->type = TransactionType::DEPOSIT;
                                $ontTransaction->status = TransactionStatus::SUCCESS;
                                $ontTransaction->address = $detail['States'][1];
                                $ontTransaction->txid = $tx['TxHash'];
                                $ontTransaction->save();
                            }
                        }
                    }
                }
            }
        }
    }

    public function gxxScan(Request $request) {

        $addresses = CoinAddress::whereNotNull('hxx_address')->limit(10)->get();

        $utility = new Utility();

        foreach ($addresses as $address) {
            $url = 'https://chainz.cryptoid.info/explorer/address.summary.dws?coin=gxx&id='.$address->hxx_address.'&r=406516&fmt.js';

            $result = $utility->curl_get_contents($url);

            var_dump($result);
            if (isset($result->tx) && count($result->tx) > 0) {
                $datetime = -1;
                foreach ($result->tx as $tx) {
                    if ($datetime == -1) {
                        $datetime = $tx[3];
                    } else {
                        $datetime += $tx[3];
                    }
                    if ($tx[4] > 0) {
                        $oldTx = Transaction::where([['dest_coin', 'GXX'], ['user_id', $address->user_id], ['txid', $tx[1]]])->first();
                        if (!$oldTx) {
                            $tran = new Transaction();

                            $tran->user_id = $address->user_id;
                            $tran->datetime = date("Y-m-d H:i:s", $datetime);
                            $tran->dest_coin = 'GXX';
                            $tran->dest_amount = $tx[4];
                            $tran->type = TransactionType::DEPOSIT;
                            $tran->status = TransactionStatus::SUCCESS;
                            $tran->txid = $tx[1];
                            $tran->save();

                            var_dump($tx[1]);
                        }
                    }
                }
            }

            if (isset($result->balance)) {
                $address->hxx_address = null;
                $address->save();
            }
        }
    }

    public function btcScan() {
        $rpcUrl = 'http://bitcoinnode:qwer1234asdf5678zxcv0987poiu5678@213.136.78.12:7788/';

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $users = User::where('email', 'onurselimerkman@gmail.com')->orderBy('id')->get();

        foreach ($users as $user) {
            $count = 0;
            $num = 10;
            \Log::info($user->id.':'.$user->email);

            $coinAddress = CoinAddress::where('user_id', $user->id)->whereNotNull('btc_address_old')->first();

            if (!$coinAddress) {
                continue;
            }

            while (true) {
                try {
                    $txs = $jsonRpc->listtransactions($user->email, $num, $num * $count);

                    if (count($txs) > 0) {
                        foreach ($txs as $tx) {
                            if ($tx['category'] == 'receive') {
                                $oldTx = Transaction::where([['dest_coin', 'BTC'], ['user_id', $user->id], ['txid', $tx['txid']], ['dest_amount', $tx['amount']]])->first();
                                if (!$oldTx) {
                                    $tran = new Transaction();

                                    $tran->user_id = $user->id;
                                    $tran->datetime = date("Y-m-d H:i:s", $tx['time']);
                                    $tran->dest_coin = 'BTC';
                                    $tran->dest_amount = $tx['amount'];
                                    $tran->type = TransactionType::DEPOSIT;
                                    $tran->status = TransactionStatus::SUCCESS;
                                    $tran->txid = $tx['txid'];
                                    $tran->save();

                                    var_dump($tx['txid']);
                                }
                            }
                        }
                    } else {
                        break;
                    }
                    $count++;
                } catch (\Exception $e) {
                    break;
                }
            }
        }
    }
    public function ccxScan() {
        $rpcUrl = env('CCX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $result = $jsonRpc->getStatus(array());
            $height = $result['blockCount'];
        } catch (\Exception $e) {
            return null;
        }

        $ccxSetting = ConcealSetting::pluck('value', 'name');

        if ($height <= $ccxSetting['block_num']) {
            return false;
        }

        $addresses = CoinAddress::whereNotNull('ccx_paymentid')->pluck('user_id', 'ccx_paymentid');

        try {
            $result = $jsonRpc->getTransactions(array(
                'blockCount' => $height - $ccxSetting['block_num'],
                'firstBlockIndex' => intval($ccxSetting['block_num']),
                'address' => $ccxSetting['wallet_address']
            ));

            if ($result['items']) {
                foreach ($result['items'] as $blockdata) {
                    foreach ($blockdata['transactions'] as $tx) {
                        if ($tx['paymentId'] != '' && isset($addresses[$tx['paymentId']])) {
                            foreach ($tx['transfers'] as $transfer) {
                                if ($transfer['address'] == $ccxSetting['wallet_address']) {
                                    $oldTx = Transaction::where([['dest_coin', 'CCX'], ['user_id', $addresses[$tx['paymentId']]], ['txid', $tx['transactionHash']]])->first();

                                    if (!$oldTx) {
                                        $ccxTx = new Transaction();

                                        $ccxTx->user_id = $addresses[$tx['paymentId']];
                                        $ccxTx->datetime = date("Y-m-d H:i:s", $tx['timestamp']);
                                        $ccxTx->dest_coin = 'CCX';
                                        $ccxTx->dest_amount = $transfer['amount'] / 1000000;
                                        $ccxTx->type = TransactionType::DEPOSIT;
                                        $ccxTx->status = TransactionStatus::PENDING;
                                        $ccxTx->txid = $tx['transactionHash'];
                                        $ccxTx->save();
                                    }
                                }
                            }
                        }
                    }
                }
            }

        } catch (\Exception $e) {

        }

        return true;
    }

    public function trxScan() {
        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $util = new Utility();

        $accounts = TronAddress::where('user_id', '<>', 1)->pluck('user_id', 'hexAddress');

        $block = 6486474;

        $url = $rpcUrl.'wallet/getblockbynum';
        $params = array(
            'num' => $block
        );

        try {
            $result = $util->curl_post($url, $params);
        } catch (\Exception $e) {
            return false;
        }

        if (!isset($result->blockID)) return false;

        if (isset($result->transactions)) {
            foreach ($result->transactions as $tx) {
                if (isset($tx->raw_data->contract[0]->type) && $tx->raw_data->contract[0]->type == 'TransferContract'
                    && isset($tx->raw_data->contract[0]->parameter->value->to_address)
                    && isset($accounts[$tx->raw_data->contract[0]->parameter->value->to_address])) {

                    $oldTx = Transaction::where([['dest_coin', 'TRX'], ['user_id', $accounts[$tx->raw_data->contract[0]->parameter->value->to_address]], ['txid', $tx->txID]])->first();

                    if (!$oldTx) {
                        $trxTransaction = new Transaction();

                        $trxTransaction->user_id = $accounts[$tx->raw_data->contract[0]->parameter->value->to_address];
                        $trxTransaction->datetime = Carbon::now();
                        $trxTransaction->dest_coin = 'TRX';
                        $trxTransaction->dest_amount = $tx->raw_data->contract[0]->parameter->value->amount * $this::TRX_UNIT;
                        $trxTransaction->type = TransactionType::DEPOSIT;
                        $trxTransaction->status = TransactionStatus::SUCCESS;
                        $trxTransaction->address = $tx->raw_data->contract[0]->parameter->value->owner_address;
                        $trxTransaction->txid = $tx->txID;
                        $trxTransaction->save();
                    }
                } else {
                    foreach ($this::TRC20_DECIMALS as $coin => $decimal) {
                        if (isset($tx->raw_data->contract[0]->type) && $tx->raw_data->contract[0]->type == 'TriggerSmartContract'
                            && isset($tx->raw_data->contract[0]->parameter->value->contract_address)
                            && $tx->raw_data->contract[0]->parameter->value->contract_address == env($coin . '_CONTRACT_HEX_ADDRESS')) {

                            $data = $tx->raw_data->contract[0]->parameter->value->data;

                            $method = substr($data, 0, 8);
                            $to = '41' . substr($data, 32, 40);
                            // $to = '0x'.ltrim(substr($data, 10, 64), '0');
                            $value = '0x' . ltrim(substr($data, -64), '0');

                            var_dump($method);
                            var_dump($to);
                            if ($method == $this::TRC20_TRANSFER_HEX && isset($accounts[$to]) && $accounts[$to] != '') {
                                $oldTx = Transaction::where([['dest_coin', $coin], ['user_id', $accounts[$to]], ['txid', $tx->txID]])->first();

                                if (!$oldTx) {
                                    $tran = new Transaction();

                                    $tran->user_id = $accounts[$to];
                                    $tran->datetime = Carbon::now();
                                    $tran->dest_coin = $coin;
                                    $tran->dest_amount = hexdec($value) / pow(10, $decimal);
                                    $tran->type = TransactionType::DEPOSIT;
                                    $tran->status = TransactionStatus::SUCCESS;
                                    $tran->txid = $tx->txID;
                                    $tran->save();
                                }
                            }
                        }
                    }

                    foreach ($this::TRC10_DECIMALS as $coin => $decimal) {
                        if (isset($tx->raw_data->contract[0]->parameter->value->asset_name)
                            && $tx->raw_data->contract[0]->parameter->value->asset_name == env($coin.'_ASSET_ID_HEX')
                            && isset($tx->raw_data->contract[0]->type) && $tx->raw_data->contract[0]->type == 'TransferAssetContract'
                            && isset($tx->raw_data->contract[0]->parameter->value->to_address)
                            && isset($accounts[$tx->raw_data->contract[0]->parameter->value->to_address])) {

                            $oldTx = Transaction::where([['dest_coin', $coin], ['user_id', $accounts[$tx->raw_data->contract[0]->parameter->value->to_address]], ['txid', $tx->txID]])->first();

                            if (!$oldTx) {
                                $tran = new Transaction();

                                $tran->user_id = $accounts[$tx->raw_data->contract[0]->parameter->value->to_address];
                                $tran->datetime = Carbon::now();
                                $tran->dest_coin = $coin;
                                $tran->dest_amount = $tx->raw_data->contract[0]->parameter->value->amount / pow(10, $decimal);
                                $tran->type = TransactionType::DEPOSIT;
                                $tran->status = TransactionStatus::SUCCESS;
                                $tran->address = $tx->raw_data->contract[0]->parameter->value->owner_address;
                                $tran->txid = $tx->txID;
                                $tran->save();

                                $addr = TronAddress::where('hexAddress', $tx->raw_data->contract[0]->parameter->value->to_address)->first();
                                if ($addr) {
                                    $addr->btt_deposited = 1;
                                    $addr->save();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function MPGScan() {
        $status = CoinScanStatus::where('coin', 'MPG')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('MPG_RPC_URL');

        $utility = new Utility();
        $url = $rpcUrl.'requestType=getBlockchainStatus';

        $timestampSetting = MPGSetting::where('name', 'timestamp')->first();
        $last_timestamp = isset($timestampSetting->value) ? $timestampSetting->value : 0;

        $mpgSettings = MPGSetting::pluck('value', 'name');

        $accounts = CoinAddress::whereNotNull('mpg_address')->pluck('user_id', 'mpg_address');

        $url = $rpcUrl.'requestType=getBlockchainTransactions&chain=5&account='.$mpgSettings['address'].'&type=0&subtype=0&executedOnly=true&timestamp='.$last_timestamp;

        $result = $utility->curl_get_contents($url);

        $maxTimestamp = $last_timestamp;
        if (isset($result) && isset($result->transactions) && count($result->transactions) > 0) {
            foreach ($result->transactions as $tx) {
                if ($tx->timestamp <= $last_timestamp) continue;
                if ($tx->timestamp > $maxTimestamp) $maxTimestamp = $tx->timestamp;

                if (isset($tx->recipientRS) && $tx->recipientRS == $mpgSettings['address']) {
                    $msg = isset($tx->attachment->message) ? $tx->attachment->message : '';
                    if (isset($accounts[$msg])) {
                        $oldTx = Transaction::where([['dest_coin', 'MPG'], ['user_id', $accounts[$msg]], ['txid', $tx->fullHash]])->first();
                        if (!$oldTx) {
                            $tran = new Transaction();

                            $tran->user_id = $accounts[$msg];
                            $tran->datetime = date("Y-m-d H:i:s", $this::MPG_INIT_TIME + $tx->timestamp);
                            $tran->dest_coin = 'MPG';
                            $tran->dest_amount = $tx->amountNQT * $this::MPG_UNIT;
                            $tran->type = TransactionType::DEPOSIT;
                            $tran->status = TransactionStatus::SUCCESS;
                            $tran->address = $tx->senderRS;
                            $tran->txid = $tx->fullHash;
                            $tran->save();
                        }
                    }
                }
            }
        }

        // \Log::info($i.":".count($result['transactions']));

        $timestampSetting->value = $maxTimestamp;
        $timestampSetting->save();

        $status->status = 0;
        $status->save();
    }
}

