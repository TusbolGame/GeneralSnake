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
export class MarketApi extends Api {
    getCoinPairInfo(data): any {
        return this.get('/market/coin_pair', data);
    }

    getUserCoinPairInfo(data): any {
        return this.get('/market/user/coin_pair', data);
    }

    getChartData(coin, marketCoin, data) {
        return this.get('/market/chart_data/' + coin + '-' + marketCoin, data);
    }

    // orders
    makeBuyOrder(coin, marketCoin, data) {
        return this.post('/order/buy/' + coin + '-' + marketCoin, data);
    }

    makeSellOrder(coin, marketCoin, data) {
        return this.post('/order/sell/' + coin + '-' + marketCoin, data);
    }

    getLastOrders(coin, marketCoin, data) {
        return this.get('/order/last/' + coin + '-' + marketCoin, data);
    }
    getBuyOrders(coin, marketCoin, data) {
        return this.get('/order/buy/' + coin + '-' + marketCoin, data);
    }

    getSellOrders(coin, marketCoin, data) {
        return this.get('/order/sell/' + coin + '-' + marketCoin, data);
    }

    getPastOrders(coin, marketCoin, data) {
        return this.get('/order/past/' + coin + '-' + marketCoin, data);
    }

    getPendingOrders(coin, marketCoin, data) {
        return this.get('/order/pending/' + coin + '-' + marketCoin, data);
    }

    getUserAllPastOrders(data) {
        return this.get('/order/past/all', data);
    }

    getUserAllPendingOrders(data) {
        return this.get('/order/pending/all', data);
    }

    cancelOrder(data) {
        return this.post('/order/delete', data);
    }

    // Fast Buy
    getFastBuyRate(coin, data) {
        return this.get('/' + coin + '/fastbuy/rate', data);
    }
    fastBuy(coin, data) {
        return this.post('/' + coin + '/fastbuy', data);
    }
    getFastBuyList(coin, data) {
        return this.get('/' + coin + '/fastbuy/list', data);
    }

}

