/**
 * Created by ApolloYr on 11/17/2017.
 */
import {Injectable} from '@angular/core';
import {environment} from '../../../environments/environment';

@Injectable()
export class SettingsService {
    public PUSHER_APP_KEY = environment.pusher_app_key;
    public PUSHER_APP_CLUSTER = environment.pusher_app_cluster;

    public siteUrl = environment.server;
    public apiUrl= environment.server + '/api';

    public ePayReceiveUrl = 'https://www.epay.com/paymentApi/merReceive';
    public ePayAccount = 'admin@sistemkoin.com';
    public ePayName = 'Sistemkoin Epay';
    public ePayMemo = '';
    public ePayLang = 'EN_US';
    public ePayEncoding = 'UTF-8';

    public user: any = {
        balance: {}
    };

    public app: any;
    public layout: any;
    public system: any;
    public coins: any;

    public rate: any = {
        USD: 0,
        EUR: 0
    };

    public loading: boolean = false;

    private storagePrefix = 'market_';

    constructor(
    ) {
        // User settings
        this.user = {};

        // App Settings
        this.app = {
            name: 'Market'
        };

        this.system = {};
    }

    getUserSetting(name) {
        return name ? this.user[name] : this.user;
    }

    setUserSetting(name, value) {
        this.user[name] = value;
    }

    getAppSetting(name) {
        return name ? this.app[name] : this.app;
    }

    setAppSetting(name, value) {
        this.app[name] = value;
    }

    getSystemSetting(name) {
        return name ? this.system[name] : this.system;
    }

    setSystemSetting(name, value) {
        if (typeof this.system[name] !== 'undefined') {
            this.system[name] = value;
        }
    }

    getBalance(name) {
        return this.user && this.user.balance && this.user.balance[name] ? this.user.balance[name] : 0;
    }

    setBalance(name, value) {
        this.user.balance[name] = value;
    }

    getUSDRate() {
        return this.rate.USD ? this.rate.USD : 0;
    }

    getEURRate() {
        return this.rate.EUR ? this.rate.EUR : 0;
    }

    clearUserSetting() {
        this.setStorage('user', false);
    }

    getStorage (key, defaultVal?) {
        return window.localStorage[this.storagePrefix + key] ?
            JSON.parse(window.localStorage[this.storagePrefix + key]) : defaultVal || false;
    }

    setStorage (key, val) {
        window.localStorage.setItem(this.storagePrefix + key, JSON.stringify(val));
    }

}

