/**
 * Created by ApolloYr on 11/17/2017.
 */

import {Injectable} from '@angular/core';
import 'rxjs/add/operator/catch';
import {SettingsService} from './settings/settings.service';
import {Router} from '@angular/router';
import {HttpClient, HttpHeaders, HttpParams} from "@angular/common/http";
import * as _ from 'lodash';
import {Observable} from "rxjs";
import {Api} from "./api.service";

@Injectable()
export class CoinApi extends Api {
    // Coin Address
    getCoinAddress(data) {
        return this.get('/coin/address', data);
    }

    // tl
    financeDeposit(currency, data) {
        return this.post('/' + currency + '/deposit', data);
    }
    financeWithdraw(currency, data) {
        return this.post('/' + currency + '/withdraw', data);
    }
    getFinanceTransaction(currency, data) {
        return this.get('/' + currency + '/transaction', data);
    }
    cancelFinanceTransaction(data) {
        return this.post('/transaction/cancel', data);
    }

    // epay
    getEpayReceiveInfo(data) {
        return this.post('/epay/receive/info', data);
    }
    // wallet
    getBalance(coin, data): any {
        return this.get('/' + coin + '/balance', data);
    }

    createWallet(coin, data) {
        return this.post('/' + coin + '/create/wallet', data);
    }

    getUnconfirmedTxList(coin, data) {
        return this.get('/' + coin + '/unconfirmedtx', data);
    }

    getWalletTxList(coin, data) {
        return this.get('/' + coin + '/wallet/gettx', data);
    }

    requestWithdraw(coin, data) {
        return this.post('/' + coin + '/withdraw/request', data);
    }

    cancelWithdraw(coin, data) {
        return this.post('/' + coin + '/withdraw/cancel', data);
    }

    // get Transaction History
    getTxHistory(coin, data) {
        return this.get('/' + coin + '/tx/history', data);
    }

    // get Deposit History
    getDepositHistory(coin, data) {
        return this.get('/' + coin + '/deposit/history', data);
    }
    // get Withdraw History
    getWithdrawHistory(coin, data) {
        return this.get('/' + coin + '/withdraw/history', data);
    }

    // Admin
    generateAdminAddress(coin, data) {
        return this.post('/' + coin + '/create/adminwallet', data);
    }
    withdrawCoin(coin, data) {
        return this.post('/' + coin + '/withdraw', data);
    }
    estimateFee(coin, data) {
        return this.post('/' + coin + '/withdraw/estimate_fee', data);
    }
    getTotalBalance(coin, data) {
        return this.get('/' + coin + '/wallet/total_balance', data);
    }
    rejectWithdraw(coin, data) {
        return this.post('/' + coin + '/withdraw/reject', data);
    }
    // XIN public Key
    getXINPublicKey(data): any {
        return this.get('/XIN/publicKey', data);
    }

    // XRP Main Wallet
    getXRPMainWallet(data): any {
        return this.get('/XRP/main_wallet', data);
    }
}

