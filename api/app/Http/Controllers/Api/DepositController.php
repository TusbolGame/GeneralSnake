<?php

namespace App\Http\Controllers\Api;

use App\Entities\AchainSetting;
use App\Entities\BenSetting;
use App\Entities\BitcoenAddress;
use App\Entities\BitsharesSetting;
use App\Entities\BitwhiteAddress;
use App\Entities\BravoSetting;
use App\Entities\BtwSetting;
use App\Entities\CoinScanSetting;
use App\Entities\ConcealSetting;
use App\Entities\MPGSetting;
use App\Entities\ONTSetting;
use App\Entities\TGNSetting;
use App\Entities\XRCSetting;
use App\Entities\CLOSetting;
use App\Entities\Coin;
use App\Entities\CoinAddress;
use App\Entities\CoinScanStatus;
use App\Entities\EthereumMove;
use App\Entities\EthereumSetting;
use App\Constants\TransactionStatus;
use App\Constants\TransactionType;
use App\Entities\EtnSetting;
use App\Entities\HtmlInvalidTransaction;
use App\Entities\MoveHistory;
use App\Entities\NextySetting;
use App\Entities\OmnicoreSetting;
use App\Entities\PirlSetting;
use App\Entities\RippleSetting;
use App\Entities\ScorumSetting;
use App\Entities\Transaction;
use App\Entities\TronAddress;
use App\Entities\TronSetting;
use App\Entities\WavesSetting;
use App\Entities\XinSetting;
use App\Libs\BitcoenRPC;
use App\Libs\JsonRPCClient;
use App\Http\Controllers\Controller;
use App\Libs\Utility;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DepositController extends Controller
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

    const PIRL_UNIT = 1 / 1000000000000000000;
    const NTY_UNIT = 1 / 1000000000000000000;

    const BTS_UNIT = 1 / 100000;
    const BTS_ASSETID = '1.3.0';
    const CVN_UNIT = 1 / 10000;
    const CVN_ASSETID = '1.3.4333';
    const BTS_ACCOUNT_NAME = 'sk-bitshares';
    const BTS_ACCOUNT_ID = '1.2.981431';
    const BTS_WALLET_PWD = 'OIjoiljcvoijsodfjoaisdjfoahwefiuschvihasoi2938r4uoisdfjkasf';
    const DEEX_UNIT = 1 / 10000;
    const DEEX_ASSETID = '1.3.2230';

    const ACT_UNIT = 1 / 100000;
    const VEX_UNIT = 1 / 100000;
    const VEX_CONTRACT = 'CON9XnhX5FtQqGFAa3KgrgkPCCEDPmuzgtSx';

    const MPG_INIT_TIME = 1514761200;
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
        'DENT' => 8,
        'LAAR' => 18,
        'ANKR' => 18,
        'W12' => 18,
        'CBEX' => 18,
        'OXY2' => 5,
        'QBIT' => 18,
        'ETHPLO' => 6,
        'SRX' => 6,
        'UPC' => 8,
        'QPY' => 18,
        'CRE' => 18,
        'ECP' => 18,
        'V4U' => 8
    );
    const CLO_UNIT = 1 / 1000000000000000000;

    const CLB_UNIT = 1 / 100000000;
    const WAVES_UNIT = 1 / 100000000;
    const ADMN_UNIT = 1 / 100000000;
    const CLB_ASSETID = 'CfMBo6GAZZhXs3bbS64NwAor24CuXwYVGJ34SSkPKHVf';
    const ADMN_ASSETID = '2ad6RZP9DVntNGNkS5so4oax2x2yZV4ZTr2yCNrpj1VN';
    //
    const ETN_UNIT = 1 / 100;

    const ONG_UNIT = 1 / 1000000000;
    const ONT_UNIT = 1;
    const ONG_CONTRACT_ADDRESS = '0200000000000000000000000000000000000000';
    const ONT_CONTRACT_ADDRESS = '0100000000000000000000000000000000000000';

    const CCX_UNIT = 1 / 1000000;

    public function detectBTCDeposit(Request $request) {
        $txid = $request->input('txid');

        \Log::info('BTC notify:'.$txid);
        $rpcUrl = env('BITCOIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        try {
            $tx = $jsonRpc->gettransaction($txid);
        } catch (\Exception $e) {
            return 'false';
        }

        $btcAddresses = CoinAddress::whereNotNull('btc_address')->pluck('user_id', 'btc_address');

        $confirmations = $tx['confirmations'];

        $details = $tx['details'];

        foreach ($details as $detail) {
            if (isset($btcAddresses[$detail['address']]) && $detail['category'] == 'receive') {
                $user_id = $btcAddresses[$detail['address']];
                $oldTx = Transaction::where([['user_id', $user_id], ['txid', $txid], ['type', TransactionType::DEPOSIT], ['dest_coin', 'BTC']])->first();

                if (!$oldTx) {
                    $tran = new Transaction();

                    $tran->user_id = $user_id;
                    $tran->datetime = date("Y-m-d H:i:s", $tx['time']);
                    $tran->dest_coin = 'BTC';
                    $tran->dest_amount = $detail['amount'];
                    $tran->type = TransactionType::DEPOSIT;
                    $tran->status = $confirmations == 0 ? TransactionStatus::PENDING : TransactionStatus::SUCCESS;
                    $tran->txid = $txid;
                    $tran->save();

                    $walletController = new WalletController();
                    $walletController->checkBTCBalance();
                } else if ($confirmations > 0) {
                    $oldTx->status = TransactionStatus::SUCCESS;
                    $oldTx->save();

                    $walletController = new WalletController();
                    $walletController->checkBTCBalance();
                }
            }
        }
    }

    public function getETHDeposit() {
        $status = CoinScanStatus::where('coin', 'ETH')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('ETHEREUM_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ethereumSetting = EthereumSetting::where('name', 'max_block')->first();

        $current_block = isset($ethereumSetting->value) ? $ethereumSetting->value : 0;

        try {
            $result = $jsonRpc->eth_blockNumber();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return 'false';
        }

        $accounts = CoinAddress::whereNotNull('eth_address')->pluck('user_id', 'eth_address');

        $current_block = hexdec($current_block);
        $max_block = hexdec($result);

        $walletController = new WalletController();

        $coinSettings = Coin::pluck('deposit', 'coin');

        for ($i = $current_block; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->eth_getBlockByNumber('0x' . dechex($i), true);
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
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
                        if ($tx['from'] == '0x471f9981f1eec2f51b11f6b440a2f2fc1b72dcd0') continue;

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
                                    $amount = hexdec($value) / pow(10, $decimal);
                                    if ($coin == 'ETHPLO') {
                                        $amount = round($amount * 0.995, 8);
                                    }

                                    $tran = new Transaction();

                                    $tran->user_id = $accounts[$to];
                                    $tran->datetime = date("Y-m-d H:i:s", hexdec($result['timestamp']));
                                    $tran->dest_coin = $coin;
                                    $tran->dest_amount = $amount;
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

            $ethereumSetting->value = '0x' . dechex($i);
            $ethereumSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getPIRLDeposit() {
        $status = CoinScanStatus::where('coin', 'PIRL')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('PIRL_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $pirlSetting = PirlSetting::where('name', 'max_block')->first();

        $current_block = isset($pirlSetting->value) ? $pirlSetting->value : 0;

        try {
            $result = $jsonRpc->eth_blockNumber();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return 'false';
        }

        $accounts = CoinAddress::whereNotNull('pirl_address')->pluck('user_id', 'pirl_address');

        $current_block = hexdec($current_block);
        $max_block = hexdec($result);

        for ($i = $current_block; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->eth_getBlockByNumber('0x' . dechex($i), true);
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
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
                        }
                    }
                }
            }

            // \Log::info($i.":".count($result['transactions']));

            $pirlSetting->value = '0x' . dechex($i);
            $pirlSetting->save();
        }
        $status->status = 0;
        $status->save();
    }


    public function getNTYDeposit() {
        $rpcUrl = env('NTY_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ntySetting = NextySetting::where('name', 'max_block')->first();

        $current_block = isset($ntySetting->value) ? $ntySetting->value : 0;

        try {
            $result = $jsonRpc->eth_blockNumber();
        } catch (\Exception $e) {
            return 'false';
        }

        $accounts = CoinAddress::whereNotNull('nty_address')->pluck('user_id', 'nty_address');

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

                        $oldTx = Transaction::where([['dest_coin', 'NTY'], ['user_id', $accounts[$tx['to']]], ['txid', $tx['hash']]])->first();

                        if (!$oldTx) {
                            $ntyTransaction = new Transaction();

                            $ntyTransaction->user_id = $accounts[$tx['to']];
                            $ntyTransaction->datetime = date("Y-m-d H:i:s", hexdec($result['timestamp']));
                            $ntyTransaction->dest_coin = 'NTY';
                            $ntyTransaction->dest_amount = hexdec($tx['value']) * $this::NTY_UNIT;
                            $ntyTransaction->type = TransactionType::DEPOSIT;
                            $ntyTransaction->status = TransactionStatus::SUCCESS;
                            $ntyTransaction->address = $tx['from'];
                            $ntyTransaction->txid = $tx['hash'];
                            $ntyTransaction->save();
                        }
                    }
                }
            }

            // \Log::info($i.":".count($result['transactions']));

            $ntySetting->value = '0x' . dechex($i);
            $ntySetting->save();
        }
    }

    public function getXRPDeposit() {
        $status = CoinScanStatus::where('coin', 'XRP')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('RIPPLE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setRippleFlag();

        $rippleSetting = RippleSetting::all()->pluck('value', 'name');

        $ledger_index_min = isset($rippleSetting['ledger_index_max']) ? $rippleSetting['ledger_index_max'] : -1;

        $ledger_index_max = -1;
        $loop = true;

        $current_ledger_index_max = $ledger_index_min;
        $first = true;
        while ($loop) {
            try {
                $result = $jsonRpc->account_tx(array(
                    'account' => $rippleSetting['address'],
                    'ledger_index_min' => $ledger_index_min,
                    'ledger_index_max' => $ledger_index_max,
                    'limit' => 5
                ));
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
                return 'false';
            }

            if ($result['status'] == 'success') {
                if ($first) {
                    $current_ledger_index_max = $result['ledger_index_max'];
                    $first = false;
                }
                foreach ($result['transactions'] as $tx) {
                    if ($tx['meta']['TransactionResult'] == 'tesSUCCESS' && $tx['tx']['TransactionType'] == 'Payment' && $tx['tx']['Destination'] == $rippleSetting['address']) {
                        if (isset($tx['tx']['DestinationTag'])) {
                            $coinAddress = CoinAddress::where('xrp_address', $tx['tx']['DestinationTag'])->first();

                            if ($coinAddress) {
                                $oldTx = Transaction::where([['dest_coin', 'XRP'], ['user_id', $coinAddress->user_id], ['txid', $tx['tx']['hash']]])->first();

                                if (!$oldTx) {
                                    $xrpTransaction = new Transaction();

                                    $xrpTransaction->user_id = $coinAddress->user_id;
                                    $xrpTransaction->datetime = date("Y-m-d H:i:s", $tx['tx']['date'] + $this::XRP_INIT_TIME);
                                    $xrpTransaction->dest_coin = 'XRP';
                                    // $xrpTransaction->dest_amount = $tx['tx']['Amount'] * $this::XRP_UNIT;
                                    $xrpTransaction->dest_amount = $tx['meta']['delivered_amount'] * $this::XRP_UNIT;
                                    $xrpTransaction->type = TransactionType::DEPOSIT;
                                    $xrpTransaction->status = TransactionStatus::SUCCESS;
                                    $xrpTransaction->address = $tx['tx']['Account'];
                                    $xrpTransaction->txid = $tx['tx']['hash'];
                                    $xrpTransaction->save();
                                }
                            }
                        }
                    }
                }
            }

            if (isset($result['marker'])) {
                $ledger_index_max = $result['marker']['ledger'];
            } else {
                $loop = false;
            }
        }

        $ledgerMax = RippleSetting::where('name', 'ledger_index_max')->first();
        if ($ledgerMax && $current_ledger_index_max > 0) {
            $ledgerMax->value = $current_ledger_index_max;
            $ledgerMax->save();
        }

        $status->status = 0;
        $status->save();
    }
    // 
    public function getXMRDeposit($user, $coinAddress) {
        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        $amount = 0;
        try {
            $result = array();
            if ($coinAddress && $coinAddress->xmr_paymentid) {
                $result = $jsonRpc->get_bulk_payments(array(
                    "payment_ids" => [$coinAddress->xmr_paymentid]
                ));
            }

            if (isset($result['payments'])) {
                foreach ($result['payments'] as $data) {
                    $amount += $data['amount'] * $this::XMR_UNIT;
                }
            }
            // $balance = $jsonRpc->getreceivedbyaccount($label);
        } catch (\Exception $e) {
            $result = array();
        }
        return $amount;
    }

    public function getXMRAllDeposit() {
        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        $depositAmount = 0;
        try {
            $result = $jsonRpc->get_transfers(array(
                'in' => true
            ));
            if ($result['in']) {
                foreach ($result['in'] as $data) {
                    $depositAmount += $data['amount'] * $this::XMR_UNIT;
                }
            }
        } catch (\Exception $e) {
            $depositAmount = 0;
        }
        return $depositAmount;
    }

    public function getXMRDepositByUser() {
        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $received = $jsonRpc->get_transfers(array(
                'in' => true
            ));
        } catch (\Exception $e) {
            $received = array();
        }

        $result = array();
        if ($received['in']) {
            foreach ($received['in'] as $rec) {
                $coinAddress = CoinAddress::where('xmr_paymentid', $rec['payment_id'])->first();

                if ($coinAddress && isset($users[$coinAddress['user_id']])) {
                    if (!isset($result[$coinAddress['user_id']])) {
                        $result[$coinAddress['user_id']] = 0;
                    }
                    $result[$coinAddress['user_id']] += $rec['amount'] * $this::XMR_UNIT;
                }
            }
        }
        return $result;
    }

    public function getXMRDepositList($user, $coinAddress) {
        $rpcUrl = env('MONERO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $result = array();
            if ($coinAddress && $coinAddress->xmr_paymentid) {
                $result = $jsonRpc->get_bulk_payments(array(
                    "payment_ids" => [$coinAddress->xmr_paymentid]
                ));
            }
        } catch (\Exception $e) {
            $result = array();
        }

        $data = array();
        if (isset($result['payments']) && count($result['payments']) > 0) {
            foreach ($result['payments'] as $tran) {
                $record = array(
                    'category' => 'receive',
                    'amount' => $tran['amount'] * $this::XMR_UNIT,
                    'txid' => $tran['tx_hash'],
                    'address' => '',
                    'confirmations' => '',
                    'time' => ''
                );

                array_push($data, $record);
            }
        }

        return $data;
    }

    // 
    public function getBENDeposit() {
        $status = CoinScanStatus::where('coin', 'BEN')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('BITCOEN_RPC_URL');

        $jsonRpc = new BitcoenRPC($rpcUrl);

        $benSetting = BenSetting::find(1);
        $maxBlock = 0;
        try {
            $info = $jsonRpc->getInfo();
            $maxBlock = $info['block']['index'];
        } catch (\Exception $e) {
        }

        $addresses = CoinAddress::whereNotNull('ben_address')->pluck('user_id', 'ben_address');

        for ($i = $benSetting->value + 1; $i <= $maxBlock; $i ++) {
            try {
                $result = $jsonRpc->getTransactionsByBlockIndex($i);

                foreach ($result as $tx) {
                    if (isset($addresses[$tx['to']])) {
                        $oldTx = Transaction::where([['user_id', $addresses[$tx['to']]], ['txid', $tx['txh']], ['type', TransactionType::DEPOSIT], ['dest_coin', 'BEN']])->first();

                        if (!$oldTx) {
                            $tran = new Transaction();

                            $tran->user_id = $addresses[$tx['to']];
                            $tran->datetime = date("Y-m-d H:i:s", $tx['timestamp'] / 1000);
                            $tran->dest_coin = 'BEN';
                            $tran->dest_amount = BitcoenRPC::mil2Ben($tx['amount']);
                            $tran->type = TransactionType::DEPOSIT;
                            $tran->status = TransactionStatus::SUCCESS;
                            $tran->txid = $tx['txh'];
                            $tran->save();
                        }
                    }
                }
            } catch (\Exception $e) {

            }

            $benSetting->value = $i;
            $benSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getXINDeposit() {
        $status = CoinScanStatus::where('coin', 'XIN')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('XIN_RPC_URL');

        $utility = new Utility();
        $url = $rpcUrl.'requestType=getBlockchainStatus';

        $result = $utility->curl_get_contents($url);

        if (!isset($result->lastBlockchainFeederHeight)) {
            $status->status = 0;
            $status->save();
            return false;
        }
        $max_block = $result->lastBlockchainFeederHeight;

        $xinSetting = XinSetting::where('name', 'max_block')->first();
        $current_block = isset($xinSetting->value) ? $xinSetting->value : 0;

        $accounts = CoinAddress::whereNotNull('xin_address')->pluck('user_id', 'xin_address');

        for ($i = $current_block; $i <= $max_block; $i++) {
            $url = $rpcUrl.'requestType=getBlock&height='.$i.'&includeTransactions=true&includeExecutedPhased=true';

            $result = $utility->curl_get_contents($url);

            if (isset($result) && isset($result->transactions) && count($result->transactions) > 0) {
                foreach ($result->transactions as $tx) {
                    if (isset($tx->recipientRS) && isset($accounts[$tx->recipientRS]) && $accounts[$tx->recipientRS] != '') {
                        $oldTx = Transaction::where([['dest_coin', 'XIN'], ['user_id', $accounts[$tx->recipientRS]], ['txid', $tx->transaction]])->first();
                        if (!$oldTx) {
                            $xinTransaction = new Transaction();

                            $xinTransaction->user_id = $accounts[$tx->recipientRS];
                            $xinTransaction->datetime = date("Y-m-d H:i:s", $this::XIN_INIT_TIME + $tx->timestamp);
                            $xinTransaction->dest_coin = 'XIN';
                            $xinTransaction->dest_amount = $tx->amountTQT * $this::XIN_UNIT;
                            $xinTransaction->type = TransactionType::DEPOSIT;
                            $xinTransaction->status = TransactionStatus::SUCCESS;
                            $xinTransaction->address = $tx->senderRS;
                            $xinTransaction->txid = $tx->transaction;
                            $xinTransaction->save();
                        }
                    }
                }
            }

            // \Log::info($i.":".count($result['transactions']));

            $xinSetting->value = $i;
            $xinSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getUSDTDeposit() {
        $status = CoinScanStatus::where('coin', 'USDT')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('OMNICORE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $omnicoreSetting = OmnicoreSetting::where('name', 'max_block')->first();

        $current_block = isset($omnicoreSetting->value) ? $omnicoreSetting->value : 0;

        try {
            $result = $jsonRpc->getblockcount();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return 'getblockcount: false';
        }

        $accounts = CoinAddress::whereNotNull('usdt_address')->pluck('user_id', 'usdt_address');

        $max_block = $result;

        for ($i = $current_block + 1; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->omni_listblocktransactions($i);
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
                return 'false';
            }

            if (count($result) > 0) {
                foreach ($result as $txid) {
                    try {
                        $tx = $jsonRpc->omni_gettransaction($txid);
                    } catch (\Exception $e) {
                        var_dump($e->getMessage());
                        continue;
                    }

                    if (isset($tx['valid']) && $tx['valid'] && $tx['ismine'] && isset($accounts[$tx['referenceaddress']]) && $accounts[$tx['referenceaddress']] != '' && $tx['propertyid'] == $this::USDT_PROPERTY) {
                        $oldTx = Transaction::where([['dest_coin', 'USDT'], ['user_id', $accounts[$tx['referenceaddress']]], ['txid', $tx['txid']]])->first();

                        if (!$oldTx) {
                            $tran = new Transaction();

                            $tran->user_id = $accounts[$tx['referenceaddress']];
                            $tran->datetime = date("Y-m-d H:i:s", $tx['blocktime']);
                            $tran->dest_coin = 'USDT';
                            $tran->dest_amount = $tx['amount'];
                            $tran->type = TransactionType::DEPOSIT;
                            $tran->status = TransactionStatus::SUCCESS;
                            $tran->address = $tx['sendingaddress'];
                            $tran->txid = $tx['txid'];
                            $tran->save();
                        }
                    }
                }
            }

            // \Log::info($i.":".count($result['transactions']));

            $omnicoreSetting->value = $i;
            $omnicoreSetting->save();
        }
        $status->status = 0;
        $status->save();
    }

    public function getUSDTUnconfirmedList($coinAddress) {
        $rpcUrl = env('OMNICORE_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);


        $data = array();
        if ($coinAddress && $coinAddress->usdt_address != '') {
            $address = $coinAddress->usdt_address;

            try {
                $result = $jsonRpc->omni_listpendingtransactions($address);
            } catch (\Exception $e) {
                $result = array();
            }

            if (count($result) > 0) {
                foreach ($result as $tran) {
                    if ($tran['referenceaddress'] == $address && $tran['propertyid'] == $this::USDT_PROPERTY) {
                        array_push($data, array(
                            'category' => 'receive',
                            'txid' => $tran['txid'],
                            'amount' => $tran['amount'],
                            'time' => Carbon::now()->toDateTimeString(),
                            'address' => $tran['sendingaddress'],
                            'status' => TransactionStatus::PENDING
                        ));
                    }
                }
            }
            return $data;
        } else {
            return $data;
        }
    }

    public function getBTWDeposit() {
        $status = CoinScanStatus::where('coin', 'BTW')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

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

        for ($i = $btwSetting->value + 1; $i <= $maxBlock; $i ++) {
            $url = $rpcUrl."blocks/get?height=".$i;

            $result = $util->curl_get_contents($url);
            $blockId = '';
            if (isset($result->success) && $result->success) {
                $blockId = $result->block->id;
            }

            if (!empty($blockId)) {
                $url = $rpcUrl."transactions?blockId=".$blockId."&limit=100";
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

            $btwSetting->value=$i;
            $btwSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getBitsharesDeposit() {
        $status = CoinScanStatus::where('coin', 'BTS')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('BITSHARES_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $btsBlockSetting = BitsharesSetting::where('name', 'bitshares_block')->first();
        $btsOpSetting = BitsharesSetting::where('name', 'bitshares_opid')->first();

        $current_block = isset($btsBlockSetting->value) ? $btsBlockSetting->value : 0;
        $current_op = isset($btsOpSetting->value) ? $btsOpSetting->value : 0;

        $accounts = CoinAddress::whereNotNull('bts_address')->pluck('user_id', 'bts_address');

        try {
            $rs = $jsonRpc->unlock($this::BTS_WALLET_PWD);
            $result = $jsonRpc->get_account_history($this::BTS_ACCOUNT_NAME, 20);
            $rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return false;
        }

        $txs = array();
        foreach ($result as $tx) {
            $op = $tx['op'];
            $opid = $op['id'];
            $blocknum = $op['block_num'];

            if ($opid <= $current_op || $blocknum < $current_block) break;
            if ($op['op'][0] != 0) continue;
            array_splice($txs, 0, 0,array(0=>$tx));
        }
        if (count($txs) > 0) {
            foreach ($txs as $tx) {
                $op = $tx['op'];
                $opid = $op['id'];
                $blocknum = $op['block_num'];

                if ($opid <= $current_op || $blocknum < $current_block) break;

                if ($op['op'][1]['to'] != $this::BTS_ACCOUNT_ID)
                    continue;

                if ($op['op'][1]['amount']['asset_id'] == $this::BTS_ASSETID) {
                    if (isset($tx['memo']) && isset($accounts[$tx['memo']]) && $accounts[$tx['memo']] != '') {
                        $oldTx = Transaction::where([['dest_coin', 'BTS'], ['user_id', $accounts[$tx['memo']]], ['txid', $opid]])->first();

                        if (!$oldTx) {
                            $cvnTransaction = new Transaction();

                            $cvnTransaction->user_id = $accounts[$tx['memo']];
                            $cvnTransaction->datetime = date("Y-m-d H:i:s");
                            $cvnTransaction->dest_coin = 'BTS';
                            $cvnTransaction->dest_amount = $op['op'][1]['amount']['amount'] * $this::BTS_UNIT;
                            $cvnTransaction->type = TransactionType::DEPOSIT;
                            $cvnTransaction->status = TransactionStatus::SUCCESS;
                            $cvnTransaction->address = $op['op'][1]['from'];
                            $cvnTransaction->txid = $opid;
                            $cvnTransaction->save();
                        } else {
                            break;
                        }
                    }
                } else if ($op['op'][1]['amount']['asset_id'] == $this::CVN_ASSETID) {
                    if (isset($tx['memo']) && isset($accounts[$tx['memo']]) && $accounts[$tx['memo']] != '') {
                        $oldTx = Transaction::where([['dest_coin', 'CVN'], ['user_id', $accounts[$tx['memo']]], ['txid', $opid]])->first();

                        if (!$oldTx) {
                            $cvnTransaction = new Transaction();

                            $cvnTransaction->user_id = $accounts[$tx['memo']];
                            $cvnTransaction->datetime = date("Y-m-d H:i:s");
                            $cvnTransaction->dest_coin = 'CVN';
                            $cvnTransaction->dest_amount = $op['op'][1]['amount']['amount'] * $this::CVN_UNIT;
                            $cvnTransaction->type = TransactionType::DEPOSIT;
                            $cvnTransaction->status = TransactionStatus::SUCCESS;
                            $cvnTransaction->address = $op['op'][1]['from'];
                            $cvnTransaction->txid = $opid;
                            $cvnTransaction->save();
                        } else {
                            break;
                        }
                    }
                } else if ($op['op'][1]['amount']['asset_id'] == $this::DEEX_ASSETID) {
                    if (isset($tx['memo']) && isset($accounts[$tx['memo']]) && $accounts[$tx['memo']] != '') {
                        $oldTx = Transaction::where([['dest_coin', 'DEEX'], ['user_id', $accounts[$tx['memo']]], ['txid', $opid]])->first();

                        if (!$oldTx) {
                            $cvnTransaction = new Transaction();

                            $cvnTransaction->user_id = $accounts[$tx['memo']];
                            $cvnTransaction->datetime = date("Y-m-d H:i:s");
                            $cvnTransaction->dest_coin = 'DEEX';
                            $cvnTransaction->dest_amount = $op['op'][1]['amount']['amount'] * $this::DEEX_UNIT;
                            $cvnTransaction->type = TransactionType::DEPOSIT;
                            $cvnTransaction->status = TransactionStatus::SUCCESS;
                            $cvnTransaction->address = $op['op'][1]['from'];
                            $cvnTransaction->txid = $opid;
                            $cvnTransaction->save();
                        } else {
                            break;
                        }
                    }
                }

                $btsBlockSetting->value = $blocknum;
                $btsBlockSetting->save();

                $btsOpSetting->value = $opid;
                $btsOpSetting->save();

            }
        }
        $status->status = 0;
        $status->save();
    }

    public function getScorumDeposit() {
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

    public function getAchainDeposit() {
        $status = CoinScanStatus::where('coin', 'ACT')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('ACHAIN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $actSetting = AchainSetting::where('name', 'block_num')->first();

        $current_block = isset($actSetting->value) ? $actSetting->value : 0;

        try {
            $result = $jsonRpc->blockchain_get_block_count();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return 'false';
        }

        $accounts = CoinAddress::whereNotNull('act_address')->pluck('user_id', 'act_address');

        $max_block = $result;

        for ($i = $current_block; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->blockchain_get_block($i);
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
                return 'false';
            }

            if (count($result['user_transaction_ids']) > 0) {
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
                        try {
                            $eventdetail = $jsonRpc->blockchain_get_events($i, $txid);
                        } catch (\Exception $e) {
                            continue;
                        }

                        if (!isset($eventdetail[0]['event_type']) || $eventdetail[0]['event_type'] != 'transfer_to_success') continue;

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

                                $vexbalance = AchainSetting::where('name', 'vex_balance')->first();
                                $vexbalance->value = round($balance * $this::ACT_UNIT, 5);
                                $vexbalance->save();
                            }
                        }
                    }
                }
            }

            // \Log::info($i.":".count($result['transactions']));

            $actSetting->value = $i;
            $actSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getTRXDeposit() {
        $status = CoinScanStatus::where('coin', 'TRX')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('TRX_FULLNODE_RPC_URL');

        $util = new Utility();

        $trxSetting = TronSetting::where('name', 'max_block')->first();

        $block = $trxSetting->value;

        $accounts = TronAddress::where('user_id', '<>', 1)->pluck('user_id', 'hexAddress');

        while(true) {
            $block ++;

            $url = $rpcUrl.'wallet/getblockbynum';
            $params = array(
                'num' => $block
            );

            try {
                $result = $util->curl_post($url, $params);
            } catch (\Exception $e) {
                break;
            }

            if (!isset($result->blockID)) break;

            if (isset($result->transactions)) {
                foreach ($result->transactions as $tx) {
                    if (isset($tx->raw_data->contract[0]->type) && $tx->raw_data->contract[0]->type == 'TransferContract'
                            && isset($tx->raw_data->contract[0]->parameter->value->to_address)
                            && isset($accounts[$tx->raw_data->contract[0]->parameter->value->to_address])) {

                        $moveTx = MoveHistory::where('txid', $tx->txID)->first();
                        if ($moveTx) {
                            continue;
                        }

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

                                        $addr = TronAddress::where('hexAddress', $to)->first();
                                        if ($addr) {
                                            $addr->igg_deposited = 1;
                                            $addr->save();
                                        }
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
            $trxSetting->value = $block;
            $trxSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getCLODeposit() {
        $status = CoinScanStatus::where('coin', 'CLO')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('CLO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $cloSetting = CLOSetting::where('name', 'max_block')->first();

        $current_block = isset($cloSetting->value) ? $cloSetting->value : 0;

        try {
            $result = $jsonRpc->eth_blockNumber();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return 'false';
        }

        $accounts = CoinAddress::whereNotNull('clo_address')->pluck('user_id', 'clo_address');

        $current_block = hexdec($current_block);
        $max_block = hexdec($result);

        $walletController = new WalletController();

        $coinSettings = Coin::pluck('deposit', 'coin');

        for ($i = $current_block; $i <= $max_block; $i++) {
            try {
                $result = $jsonRpc->eth_getBlockByNumber('0x' . dechex($i), true);
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
                return 'false';
            }

            if (count($result['transactions']) > 0) {
                foreach ($result['transactions'] as $tx) {
                    if (isset($accounts[$tx['to']]) && $accounts[$tx['to']] != '' && $coinSettings['CLO'] == 1) {

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
                            $ethTransaction->dest_amount = hexdec($tx['value']) * $this::CLO_UNIT;
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

            // \Log::info($i.":".count($result['transactions']));

            $cloSetting->value = '0x' . dechex($i);
            $cloSetting->save();
        }
        $status->status = 0;
        $status->save();
    }



    public function getWavesDeposit() {
        $status = CoinScanStatus::where('coin', 'WAVES')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('WAVES_RPC_URL');

        $util = new Utility();

        $wavesSetting = WavesSetting::where('name', 'max_block')->first();

        $block = $wavesSetting->value;

        $accounts = CoinAddress::whereNotNull('waves_address')->pluck('user_id', 'waves_address');

        while(true) {
            $url = $rpcUrl.'blocks/at/'.$block;

            try {
                $result = $util->curl_get_contents($url);
            } catch (\Exception $e) {
                break;
            }

            if (is_null($result)) {
                break;
            }

            if (isset($result->status) && $result->status == 'error') {
                break;
            }

            if (isset($result->transactions)) {
                foreach ($result->transactions as $tx) {
                    if (isset($tx->type) && $tx->type == 4 && isset($tx->sender) && isset($accounts[$tx->recipient])) {

                        if (is_null($tx->assetId)) {
                            $oldTx = Transaction::where([['dest_coin', 'WAVES'], ['user_id', $accounts[$tx->recipient]], ['txid', $tx->id]])->first();

                            if (!$oldTx) {
                                $wavesTransaction = new Transaction();

                                $wavesTransaction->user_id = $accounts[$tx->recipient];
                                $wavesTransaction->datetime = date("Y-m-d H:i:s", intval($tx->timestamp / 1000));
                                $wavesTransaction->dest_coin = 'WAVES';
                                $wavesTransaction->dest_amount = $tx->amount * $this::WAVES_UNIT;
                                $wavesTransaction->type = TransactionType::DEPOSIT;
                                $wavesTransaction->status = TransactionStatus::SUCCESS;
                                $wavesTransaction->address = $tx->sender;
                                $wavesTransaction->txid = $tx->id;
                                $wavesTransaction->save();
                            }
                        } else if ($tx->assetId == $this::CLB_ASSETID) {
                            $oldTx = Transaction::where([['dest_coin', 'CLB'], ['user_id', $accounts[$tx->recipient]], ['txid', $tx->id]])->first();

                            if (!$oldTx) {
                                $wavesTransaction = new Transaction();

                                $wavesTransaction->user_id = $accounts[$tx->recipient];
                                $wavesTransaction->datetime = date("Y-m-d H:i:s", intval($tx->timestamp / 1000));
                                $wavesTransaction->dest_coin = 'CLB';
                                $wavesTransaction->dest_amount = $tx->amount * $this::CLB_UNIT;
                                $wavesTransaction->type = TransactionType::DEPOSIT;
                                $wavesTransaction->status = TransactionStatus::SUCCESS;
                                $wavesTransaction->address = $tx->sender;
                                $wavesTransaction->txid = $tx->id;
                                $wavesTransaction->save();
                            }
                        } else if ($tx->assetId == $this::ADMN_ASSETID) {
                            $oldTx = Transaction::where([['dest_coin', 'ADMN'], ['user_id', $accounts[$tx->recipient]], ['txid', $tx->id]])->first();

                            if (!$oldTx) {
                                $wavesTransaction = new Transaction();

                                $wavesTransaction->user_id = $accounts[$tx->recipient];
                                $wavesTransaction->datetime = date("Y-m-d H:i:s", intval($tx->timestamp / 1000));
                                $wavesTransaction->dest_coin = 'ADMN';
                                $wavesTransaction->dest_amount = $tx->amount * $this::ADMN_UNIT;
                                $wavesTransaction->type = TransactionType::DEPOSIT;
                                $wavesTransaction->status = TransactionStatus::SUCCESS;
                                $wavesTransaction->address = $tx->sender;
                                $wavesTransaction->txid = $tx->id;
                                $wavesTransaction->save();
                            }
                        }
                    }
                }
            }
            $wavesSetting->value = $block;
            $wavesSetting->save();

            $block ++;
        }

        $status->status = 0;
        $status->save();
    }

    public function getETNDeposit() {
        $status = CoinScanStatus::where('coin', 'ETN')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('ETN_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
//            $jsonRpc->open_wallet(array(
//                'filename' => 'sistemkoin1',
//                'password' => 'Ljoiswjfeoaiwjefoijalixzjcvoijheofujoiw4ur59384u598ujgoaisjgoaisdfj9283u40934i50'
//            ));
            $result = $jsonRpc->getheight(array());
            $height = $result['height'];

        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return null;
        }

        $etnSetting = EtnSetting::where('name', 'max_block')->first();

        $block = intval($etnSetting->value);

        $addresses = CoinAddress::whereNotNull('etn_paymentid')->pluck('user_id', 'etn_paymentid');

        $amount = 0;
        try {
            $result = array();

            $result = $jsonRpc->get_transfers(array(
                'in' => true,
                'filter_by_height' => true,
                'min_height' => $block-20,
                'max_height' => $height
            ));

            if ($result['in']) {
                foreach ($result['in'] as $data) {
                    if (isset($addresses[$data['payment_id']])) {
                        $oldTx = Transaction::where([['dest_coin', 'ETN'], ['user_id', $addresses[$data['payment_id']]], ['txid', $data['txid']]])->first();

                        if (!$oldTx) {
                            $etnTransaction = new Transaction();

                            $etnTransaction->user_id = $addresses[$data['payment_id']];
                            $etnTransaction->datetime = date("Y-m-d H:i:s", $data['timestamp']);
                            $etnTransaction->dest_coin = 'ETN';
                            $etnTransaction->dest_amount = $data['amount'] * $this::ETN_UNIT;
                            $etnTransaction->type = TransactionType::DEPOSIT;
                            $etnTransaction->status = TransactionStatus::PENDING;
                            //$ethTransaction->address = $tx['from'];
                            $etnTransaction->txid = $data['txid'];
                            $etnTransaction->save();

                        }
                    }
                }
            }

        } catch (\Exception $e) {

        }

        $etnSetting->value = $height;
        $etnSetting->save();

        $pendings = Transaction::where([['dest_coin', 'ETN'], ['status', TransactionStatus::PENDING], ['type', TransactionType::DEPOSIT]])->get();

        foreach ($pendings as $pending) {
            try {
                $result = $jsonRpc->get_transfer_by_txid(array(
                    'txid' => $pending->txid
                ));

                if ($height - $result['transfer']['height'] >= 19) {
                    $pending->status = TransactionStatus::SUCCESS;
                    $pending->save();
                }
            } catch (\Exception $e) {
                continue;
            }

        }

        $status->status = 0;
        $status->save();
//        $jsonRpc->close_wallet(array());
        return true;
    }

    public function getXRCDeposit() {
        $status = CoinScanStatus::where('coin', 'XRC')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('XRC_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $xrcSetting = XRCSetting::pluck('value', 'name');

        $current_block = isset($xrcSetting['max_block']) ? $xrcSetting['max_block'] : 1;

        $accounts = CoinAddress::whereNotNull('xrc_address')->pluck('user_id', 'xrc_address');

        $walletController = new WalletController();

        $count = 10;
        $from = 0;
        while($from < 100) {
            try {
                $txs = $jsonRpc->listtransactions($xrcSetting['walletname'], $count, $from);
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
                return 'false';
            }

            if (count($txs) > 0) {
                foreach ($txs as $tx) {
                    if ($tx['blockheight'] < $current_block) break 2;

                    if ($tx['category'] == 'receive' && isset($accounts[$tx['address']]) && $accounts[$tx['address']] != '') {

                        $oldTx = Transaction::where([['dest_coin', 'XRC'], ['user_id', $accounts[$tx['address']]], ['txid', $tx['txid']]])->first();

                        if (!$oldTx) {
                            $xrcTransaction = new Transaction();

                            $xrcTransaction->user_id = $accounts[$tx['address']];
                            $xrcTransaction->datetime = date("Y-m-d H:i:s", $tx['time']);
                            $xrcTransaction->dest_coin = 'XRC';
                            $xrcTransaction->dest_amount = $tx['amount'];
                            $xrcTransaction->type = TransactionType::DEPOSIT;
                            $xrcTransaction->status = TransactionStatus::SUCCESS;
                            $xrcTransaction->txid = $tx['txid'];
                            $xrcTransaction->save();
                        }
                    }
                }
            }
            $from += 10;
        }

        try {
            $count = $jsonRpc->getblockcount();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return 'false';
        }

        $xrcSetting = XRCSetting::where('name', 'max_block')->first();
        $xrcSetting->value = $count;
        $xrcSetting->save();

        $status->status = 0;
        $status->save();
    }

    public function getONTDeposit() {
        $status = CoinScanStatus::where('coin', 'ONT')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('ONT_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $ontSetting = ONTSetting::where('name', 'max_block')->first();

        $current_block = isset($ontSetting->value) ? $ontSetting->value : 1;

        try {
            $blockcount = $jsonRpc->getblockcount();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return 'false';
        }

        $accounts = CoinAddress::whereNotNull('ont_address')->pluck('user_id', 'ont_address');

        for ($i = $current_block + 1; $i <= $blockcount; $i ++) {
            try {
                $txs = $jsonRpc->getsmartcodeevent($i);
            } catch (\Exception $e) {
                $status->status = 0;
                $status->save();
                return 'false';
            }

            if (count($txs) > 0) {
                foreach ($txs as $tx) {
                    if ($tx['State'] == 1 && count($tx['Notify']) > 0) {
                        foreach ($tx['Notify'] as $detail) {
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

            $ontSetting->value = $i;
            $ontSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getCCXDeposit() {
        $status = CoinScanStatus::where('coin', 'CCX')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('CCX_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);
        $jsonRpc->setParametersStructure('object');

        try {
            $result = $jsonRpc->getStatus(array());
            $height = $result['blockCount'];
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return null;
        }

        $ccxSetting = ConcealSetting::pluck('value', 'name');

        if ($height <= $ccxSetting['block_num']) {
            $status->status = 0;
            $status->save();
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
                                        $ccxTx->dest_amount = $transfer['amount'] * $this::CCX_UNIT;
                                        $ccxTx->type = TransactionType::DEPOSIT;
                                        $ccxTx->status = TransactionStatus::SUCCESS;
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

        $ccxSetting = ConcealSetting::where('name', 'block_num')->first();
        $ccxSetting->value = $height;
        $ccxSetting->save();

        $status->status = 0;
        $status->save();

        return true;
    }

    public function getBRAVODeposit() {
        $status = CoinScanStatus::where('coin', 'BRAVO')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $rpcUrl = env('BRAVO_RPC_URL');

        $jsonRpc = new JsonRPCClient($rpcUrl);

        $blockSetting = BravoSetting::where('name', 'block')->first();
        $opSetting = BravoSetting::where('name', 'opid')->first();

        $current_block = isset($blockSetting->value) ? $blockSetting->value : 0;
        $current_op = isset($opSetting->value) ? $opSetting->value : 0;

        $accounts = CoinAddress::whereNotNull('bravo_address')->pluck('user_id', 'bravo_address');

        try {
            //$rs = $jsonRpc->unlock('BRAVOklsdjfoaijwr0293i490iasdofjasoildfjoaiwejfoiaw');
            $result = $jsonRpc->get_account_history('sistemkoin', -1, 20);
            //$rs = $jsonRpc->lock();
        } catch (\Exception $e) {
            $status->status = 0;
            $status->save();
            return false;
        }

        $txs = array();
        foreach ($result as $tx) {
            $op = $tx[1]['op'];
            $opid = $tx[0];
            $blocknum = $tx[1]['block'];

            if ($opid <= $current_op || $blocknum <= $current_block) continue;
            if ($op[0] != 'transfer') continue;

            if ($op[1]['to'] != 'sistemkoin')
                continue;

            if (substr($op[1]['amount'], -5) == 'BRAVO') {
                if (isset($op[1]['memo']) && isset($accounts[$op[1]['memo']]) && $accounts[$op[1]['memo']] != '') {
                    $oldTx = Transaction::where([['dest_coin', 'BRAVO'], ['user_id', $accounts[$op[1]['memo']]], ['txid', $blocknum]])->first();

                    if (!$oldTx) {
                        $transaction = new Transaction();

                        $transaction->user_id = $accounts[$op[1]['memo']];
                        $transaction->datetime = date("Y-m-d H:i:s");
                        $transaction->dest_coin = 'BRAVO';
                        $transaction->dest_amount = floatval($op[1]['amount']);
                        $transaction->type = TransactionType::DEPOSIT;
                        $transaction->status = TransactionStatus::SUCCESS;
                        $transaction->address = $op[1]['from'];
                        $transaction->txid = $blocknum; //$tx[1]['trx_id'];
                        $transaction->save();
                    } else {
                        continue;
                    }
                }
            }

            $blockSetting->value = $blocknum;
            $blockSetting->save();

            $opSetting->value = $opid;
            $opSetting->save();
        }
        $status->status = 0;
        $status->save();
    }

    public function getMPGDeposit() {
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

        $url = $rpcUrl.'requestType=getBlockchainTransactions&chain=5&account='.$mpgSettings['address'].'&type=0&subtype=0&executedOnly=true&includePhasingResult=true&timestamp='.$last_timestamp;

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

    public function getTGNDeposit() {
        $status = CoinScanStatus::where('coin', 'TGN')->first();
        if ($status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

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
            if (isset($result->access_token) && $result->access_token) {
                $tgnSetting->authorization = $result->token_type.' '.$result->access_token;
                $tgnSetting->auth_validtime = time() + $result->expires_in;
                $tgnSetting->save();
            }
        }

        $result = $utility->tgn_curl_post($rpcUrl.'/Transaction/GetTransactionList',
            array(
                'ApiKey' => $tgnSetting->api_key,
                'SecretKey' => $tgnSetting->secret_key,
            ),
            true,
            $tgnSetting->authorization
        );

        $tgnAddresses = CoinAddress::whereNotNull('tgn_address')->pluck('user_id', 'tgn_address');

        if (isset($result->Status) && $result->Status && count($result->Data) > 0) {
            foreach ($result->Data as $key=>$tx) {
                if ($key == 0) {
                    $last_txhash = $tx->TransactionHash;
                    $last_txdate = $tx->TransactionDate;
                }

                if ($tx->TransactionHash == $tgnSetting->last_txhash) break;
                if ($tx->TransactionDate < $tgnSetting->last_txdate) break;


                if (isset($tgnAddresses[$tx->OutputAddress])) {
                    $oldTx = Transaction::where([['dest_coin', 'TGN'], ['user_id', $tgnAddresses[$tx->OutputAddress]], ['txid', $tx->TransactionHash]])->first();

                    if (!$oldTx) {
                        $transaction = new Transaction();

                        $transaction->user_id = $tgnAddresses[$tx->OutputAddress];
                        $transaction->datetime = $tx->TransactionDate;
                        $transaction->dest_coin = 'TGN';
                        $transaction->dest_amount = $tx->OutputAmount;
                        $transaction->type = TransactionType::DEPOSIT;
                        $transaction->status = TransactionStatus::SUCCESS;
                        $transaction->address = $tx->InputAddress;
                        $transaction->txid = $tx->TransactionHash;
                        $transaction->save();
                    } else {
                        continue;
                    }
                }
            }

            $tgnSetting->last_txhash = $last_txhash;
            $tgnSetting->last_txdate = $last_txdate;
            $tgnSetting->save();
        }

        $status->status = 0;
        $status->save();
    }

    public function getCoinDeposit() {
        $status = CoinScanStatus::where('coin', 'ALL')->first();
        if ($status && $status->status == 1) return 'false';

        $status->status = 1;
        $status->save();

        $settings = CoinScanSetting::all();

        foreach ($settings as $setting) {
            $rpcUrl = env($setting->coin.'_RPC_URL');

            $users = CoinAddress::whereNotNull(strtolower($setting->coin).'_address')->pluck('user_id', strtolower($setting->coin).'_address');

            $jsonRpc = new JsonRPCClient($rpcUrl);

            $stopped = false;

            $first = true;
            $lasttime = $setting->time;
            $lasttxid = $setting->txid;
            $lastblock = $setting->blockhash;

            $count = 0;
            $num = 10;
            while(true) {
                try {
                    $result = $jsonRpc->listtransactions("*", $num, $num * $count);

                    if (count($result) > 0) {
                        for ($i = count($result) - 1; $i >= 0; $i --) {
                            $tran = $result[$i];
                            if (!is_null($setting->txid) && $tran['txid'] == $setting->txid) {
                                $stopped = true;
                                break;
                            }
                            if (!is_null($setting->time) && $tran['time'] < $setting->time) {
                                $stopped = true;
                                break;
                            }
                            if (isset($tran['confirmations']) && $tran['confirmations'] == 0) {
                                continue;
                            }
                            if (isset($tran['generated']) && $tran['generated']) {
                                continue;
                            }

                            if ($tran['category'] == 'receive' && isset($tran['address']) && isset($users[$tran['address']])) {
                                if ($setting->new == 1) {
                                    $tx = new Transaction();

                                    $tx->user_id = $users[$tran['address']];
                                    $tx->datetime = date("Y-m-d H:i:s", $tran['time']);
                                    $tx->dest_coin = $setting->coin;
                                    $tx->dest_amount = $tran['amount'];
                                    $tx->type = TransactionType::DEPOSIT;
                                    $tx->status = TransactionStatus::SUCCESS;
                                    $tx->txid = $tran['txid'];
                                    $tx->save();
                                } else {
                                    $oldTx = Transaction::where([['user_id', $users[$tran['address']]], ['txid', $tran['txid']], ['type', TransactionType::DEPOSIT], ['dest_coin', $setting->coin]])->first();

                                    if (!$oldTx) {
                                        $tx = new Transaction();

                                        $tx->user_id = $users[$tran['address']];
                                        $tx->datetime = date("Y-m-d H:i:s", $tran['time']);
                                        $tx->dest_coin = $setting->coin;
                                        $tx->dest_amount = $tran['amount'];
                                        $tx->type = TransactionType::DEPOSIT;
                                        $tx->status = TransactionStatus::SUCCESS;
                                        $tx->txid = $tran['txid'];
                                        $tx->save();
                                    }
                                }
                            }
                            if ($first) {
                                $lasttime = $tran['time'];
                                $lastblock = $tran['blockhash'];
                                $lasttxid = $tran['txid'];
                                $first = false;
                            }
                        }
                    } else {
                        break;
                    }
                    if ($stopped) {
                        break;
                    }
                    $count ++;
                } catch (\Exception $e) {
                    break;
                }
            }

            $setting->time = $lasttime;
            $setting->txid = $lasttxid;
            $setting->blockhash = $lastblock;
            $setting->save();
        }

        $status->status = 0;
        $status->save();
    }
}
