/**
 * Created by ApolloYr on 11/17/2017.
 */

import {Injectable} from '@angular/core';
import {FormBuilder, FormGroup, Validators, FormControl, AbstractControl} from '@angular/forms';
import {Api} from "./api.service";
import {SettingsService} from "./settings/settings.service";
import {CoinApi} from "./coinApi.service";

@Injectable()
export class BalanceService {
    constructor(public api: Api,
                public coinApi: CoinApi,
                public settings: SettingsService) {
    }

    init() {

    }

    getCoinBalance(coin) {
        this.coinApi.getBalance(coin, {}).subscribe(res => {
            if (res.success) {
                this.settings.setBalance(coin, res.balance);
            } else {
            }
        }, err => {
        });
    }

    getSystemSettings() {
        this.api.getSystemSettings({}).subscribe(res => {
            if (res.success) {
                this.settings.system = res.data;
            }
        }, err => {
        });
    }
}

