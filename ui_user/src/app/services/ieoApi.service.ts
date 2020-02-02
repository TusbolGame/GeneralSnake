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
export class IeoApi extends Api {
    getCoins(data): any {
        return this.get('/ieo/coins', data);
    }
    getCoinPairs(data): any {
        return this.get('/ieo/pairs', data);
    }
    getCoinLinks(data): any {
        return this.get('/ieo/links', data);
    }
    getCoinBonus(data): any {
        return this.get('/ieo/bonus', data);
    }
    getStatistics(data): any {
        return this.get('/ieo/sts', data);
    }
    getTradeHistory(data): any {
        return this.get('/ieo/history', data);
    }
    // get Last Price
    getLastPrice(marketCoin, coin) {
        return this.get('/price/last/' + marketCoin + '-' + coin, {});
    }
    buyToken(data): any {
        return this.post('/ieo/buy', data);
    }
}

