import {Component, AfterViewInit, OnInit} from '@angular/core';
import {SettingsService} from "../../../services/settings/settings.service";
import {Notifications} from "../../../services/notifications.service";
import {BalanceService} from "../../../services/balance.service";
import {CoinApi} from "../../../services/coinApi.service";
import {Api} from "../../../services/api.service";
import {MarketApi} from "../../../services/marketApi.service";
import {Router} from "@angular/router";

declare var swal: any;
declare const ripple: any;
declare var bchaddr: any;
declare var $: any;

@Component({
    templateUrl: './balance.component.html',
    styleUrls: ['./balance.component.scss']
})
export class BalanceComponent implements OnInit, AfterViewInit {
    all_coins: any = [];
    coins: any = [];
    pairs: any = {
        TL: {},
        BTC: {},
        ETH: {},
        XRP: {},
        USD: {},
        USDT: {},
        EURO: {}
    };
    btc_tl = 0;
    loading = true;

    sortColumn = 'total_amount';
    sortOrder = 'desc';

    filtertxt = '';
    total_btc = 0;

    constructor(
                public router: Router,
                public api: Api,
                public marketApi: MarketApi,
                public settings: SettingsService,
                public notify: Notifications,
                public balance: BalanceService) {

    }

    ngOnInit() {
        this.marketApi.getCoinPairInfo({}).subscribe(res => {
            if (res.success) {
                for (let i = 0; i < res.data.length; i ++) {
                    if (res.data[i].market_coin != 'TL' && res.data[i].market_coin != 'BTC' && res.data[i].market_coin != 'ETH' && res.data[i].market_coin != 'USD' && res.data[i].market_coin != 'USDT' &&
                        res.data[i].market_coin != 'XRP' && res.data[i].market_coin != 'EURO') continue;
                    this.pairs[res.data[i].market_coin][res.data[i].coin] = res.data[i];
                }

                this.btc_tl = this.pairs.TL['BTC'].last_price;

                this.api.getAccountBalance().subscribe(res => {
                    this.loading = false;
                    if (res.success) {
                        for (let i = 0; i < res.data.length; i ++) {
                            let coin = res.data[i];
                            if (coin.coin == 'BTC') {
                                res.data[i].btc_price = 1;
                            } else if (coin.coin == 'TL' && this.btc_tl > 0) {
                                res.data[i].btc_price = 1 / this.btc_tl;
                            } else if (coin.coin == 'USD' && this.pairs.USD['BTC'].last_price > 0) {
                                res.data[i].btc_price = 1 / this.pairs.USD['BTC'].last_price;
                            } else if (coin.coin == 'EURO' && this.pairs.EURO['BTC'].last_price > 0) {
                                res.data[i].btc_price = 1 / this.pairs.EURO['BTC'].last_price;
                            } else if (coin.coin == 'USDT' && this.pairs.USDT['BTC'].last_price > 0) {
                                res.data[i].btc_price = 1 / this.pairs.USDT['BTC'].last_price;
                            } else if (this.pairs.TL[coin.coin] && this.pairs.TL[coin.coin].last_price && this.btc_tl > 0) {
                                res.data[i].btc_price = this.pairs.TL[coin.coin].last_price / this.btc_tl;
                            } else if (this.pairs.BTC[coin.coin] && this.pairs.BTC[coin.coin].last_price) {
                                res.data[i].btc_price = this.pairs.BTC[coin.coin].last_price;
                            } else {
                                res.data[i].btc_price = 0;
                            }

                            this.total_btc += res.data[i].available_amount * res.data[i].btc_price;
                        }

                        this.all_coins = res.data;
                        this.filterCoin();
                    }
                }, err => {
                    this.loading = false;
                });
            } else {
                this.loading = false;
            }
        }, err=> {
            this.loading = false;
        });
    }

    ngAfterViewInit() {
    }

    onClickDeposit(coin) {
        if (coin == 'TL') {
            this.router.navigate(['/lira']);
        } else if (coin == 'USD') {
            this.router.navigate(['/usd']);
        } else if (coin == 'EURO') {
            this.router.navigate(['/euro']);
        } else {
            window.localStorage.setItem('deposit_coin', coin);
            this.router.navigate(['/wallet/deposit']);
        }
    }

    onClickWithdraw(coin) {
        if (coin == 'TL') {
            this.router.navigate(['/lira']);
        } else if (coin == 'USD') {
            this.router.navigate(['/usd']);
        } else if (coin == 'EURO') {
            this.router.navigate(['/euro']);
        } else {
            window.localStorage.setItem('withdraw_coin', coin);
            this.router.navigate(['/wallet/withdraw']);
        }
    }

    filterCoin() {
        this.coins = this.all_coins.filter(coin => coin.coin.toLowerCase().indexOf(this.filtertxt.toLowerCase()) !== -1 || coin.fullname.toLowerCase().indexOf(this.filtertxt.toLowerCase()) !== -1);

        if (this.sortColumn != '') {
            let _parent = this;
            this.coins = this.coins.sort(function (r1, r2) {
                let r1v, r2v;
                if (_parent.sortColumn == 'btc_value') {
                    r1v = r1['available_amount'] * r1['btc_price'];
                    r2v = r2['available_amount'] * r2['btc_price'];
                } else {
                    r1v = r1[_parent.sortColumn];
                    r2v = r2[_parent.sortColumn];

                }

                if (!isNaN(r1v)) r1v = Number(r1v);
                if (!isNaN(r2v)) r2v = Number(r2v);

                if (_parent.sortOrder == 'asc') {
                    return r1v > r2v ? 1 : (r1v == r2v ? 0 : -1);
                } else {
                    return r1v < r2v ? 1 : (r1v == r2v ? 0 : -1);
                }
            });
        }
    }

    onFilter() {
        this.filterCoin();
    }

    onSortTable(header) {
        if (this.sortColumn == header && this.sortOrder == 'desc') {
            this.sortOrder = 'asc';
        } else {
            this.sortOrder = 'desc';
        }

        this.sortColumn = header;

        this.filterCoin();
    }
}