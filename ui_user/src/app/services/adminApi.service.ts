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
export class AdminApi extends Api {
    // Admin Referral
    getAdminReferral(data) {
        return this.get('/admin/referral', data);
    }
    getAdminRefCommission(data) {
        return this.get('/admin/referral/commission', data);
    }
    getAdminCommissionHistory(data) {
        return this.get('/admin/referral/commission/history', data);
    }

    // Coin
    saveCoinInfo(data) {
        return this.post('/admin/coin/update', data);
    }
    // delete User
    deleteUser(data) {
        return this.post('/admin/user/delete', data);
    }
    // Admin
    getUserById(user_id) {
        return this.get('/admin/user/id/' + user_id, {});
    }
    getUserByEmail(email) {
        return this.get('/admin/user/email/' + email, {});
    }
    getUsers(data) {
        return this.get('/admin/users', data);
    }
    getCoinUsers(coin, data) {
        return this.get('/admin/users/' + coin, data);
    }
    getUserBalance(data) {
        return this.get('/admin/user/balance', data);
    }
    getUserCoinHistory(coin, data) {
        return this.get('/admin/user/' + coin + '/history', data);
    }

    addCoinManual(coin, data) {
        return this.post('/admin/user/' + coin + '/addManual', data);
    }
    deleteCoinManual(coin, data) {
        return this.post('/admin/user/' + coin + '/deleteManual', data);
    }

    getWithdrawPending(coin, data) {
        return this.get('/admin/' + coin + '/withdraw/pending', data);
    }
    getWithdrawCountByCoin() {
        return this.get('/admin/withdraw/count', {});
    }
    getWithdrawHistory(coin, data) {
        return this.get('/admin/' + coin + '/withdraw/history', data);
    }
    getWithdrawDetail(coin, data) {
        return this.get('/admin/' + coin + '/withdraw/detail', data);
    }

    getUserProfile(data) {
        return this.get('/admin/user/profile', data);
    }

    getAdminSettings(data) {
        return this.get('/admin/settings', data);
    }
    getDepositHistory(coin, data) {
        return this.get('/admin/' + coin + '/deposit/history', data);
    }
    getMarketOpenOrders(coin, marketCoin, data) {
        return this.get('/admin/market/orders/' + coin + '-' + marketCoin, data);
    }
    deleteMarketOrder(coin, data) {
        return this.post('/order/delete', data);
    }

    getMarketHistory(coin, marketCoin, data) {
        return this.get('/admin/market/history/' + coin + '-' + marketCoin, data);
    }
    deleteMarketHistory(data) {
        return this.post('/admin/markettx/delete', data);
    }
    // TL
    getFinanceDepositPending(currency, data) {
        return this.get('/admin/' + currency + '/deposit/pending', data);
    }
    withdrawFinancePending(currency, data) {
        return this.post('/admin/' + currency + '/withdraw/pending', data);
    }
    depositFinancePending(currency, data) {
        return this.post('/admin/' + currency + '/deposit', data);
    }
    rejectFinancePending(currency, data) {
        return this.post('/admin/' + currency + '/deposit/reject', data);
    }
    deleteFinancePending(currency, data) {
        return this.post('/admin/' + currency + '/pending/delete', data);
    }

    // Fastbuy History
    getFastbuyHistory(coin, data) {
        return this.get('/admin/' + coin + '/fastbuy/history', data);
    }
    // Admin Settings
    addSetting(data) {
        return this.post('/admin/settings/add', data);
    }
    deleteSetting(data) {
        return this.post('/admin/settings/delete', data);
    }
    saveSetting(data) {
        return this.post('/admin/settings/save', data);
    }

    // Statistics
    getUserStatistics(data) {
        return this.get('/admin/users/statistics', data);
    }
    getCoinStatistics(data) {
        return this.get('/admin/coin/statistics', data);
    }

    // Erc20 Wallet
    getErc20Wallet(coin) {
        return this.get('/admin/erc20/' + coin + '/wallet', {});
    }
    // All Wallet
    getAllWallet(coin) {
        return this.get('/admin/' + coin + '/wallet/all', {});
    }

    // move Wallet
    moveWalletToMain(coin, data) {
        return this.post('/admin/' + coin + '/wallet/move', data);
    }

    // ICO History
    getICOHistory(coin, data) {
        return this.get('/admin/' + coin + '/ico/history', data);
    }
}

