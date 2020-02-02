import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {Router} from "@angular/router";
import {SettingsService} from "../../services/settings/settings.service";
import {Notifications} from "../../services/notifications.service";
import {BalanceService} from "../../services/balance.service";
import {IeoApi} from "../../services/ieoApi.service";
import {Validate} from "../../services/validate.service";

declare var swal: any;
declare var $: any;
declare var particlesJS: any;

@Component({
    templateUrl: './ieo.component.html',
    styleUrls: ['./ieo.component.scss']
})
export class IEOComponent implements OnInit, AfterViewInit, OnDestroy {
    coins: any = [];

    all_pairs: any = [];
    pairs: any;

    selPair: any = {};
    selCoin = '';

    selMarketCoin = 'TL';
    activeMarketCoin = 'TL';

    filtertxt = '';

    sortInfo: any = {
        TL : {sortColumn: 'coin', sortOrder: 'asc'},
        BTC : {sortColumn: 'coin', sortOrder: 'asc'},
        ETH : {sortColumn: 'coin', sortOrder: 'asc'},
        USD : {sortColumn: 'coin', sortOrder: 'asc'},
    };
    coinLinks: any = [];
    bonus: any = [];

    ieoSetting: any = {
        ieo_amount: 0
    };

    buy: any = {
        price: 0.0123123,
        amount: 0,
        marketcoin_total: 0,
        usd_total: 0
    };

    history: any = [];

    usd_tl_price = 0;
    btc_usd_price = 0;
    eth_usd_price = 0;

    constructor(public router: Router,
                public settings: SettingsService,
                public balance: BalanceService,
                public ieoApi: IeoApi,
                public notify: Notifications,
                public validate: Validate
    ) {

    }

    ngOnInit() {

        this.ieoApi.getCoinPairs({}).subscribe(res => {
            if (res.success) {
                if (res.success) {
                    this.all_pairs.TL = res.data.filter(pair => pair.market_coin === 'TL');
                    this.all_pairs.BTC = res.data.filter(pair => pair.market_coin === 'BTC');
                    this.all_pairs.ETH = res.data.filter(pair => pair.market_coin === 'ETH');
                    this.all_pairs.USD = res.data.filter(pair => pair.market_coin === 'USD');

                    this.filterCoin();

                    if (this.selCoin == '') {
                        this.selCoin = this.all_pairs.TL[0].coin;
                    }

                    this.selPair = res.data.filter(pair => pair.coin === this.selCoin && pair.market_coin === this.selMarketCoin)[0];

                    this.ieoApi.getLastPrice('TL', 'USDT').subscribe(res => {
                        if (res.success) {
                            this.usd_tl_price = res.price;
                        } else {
                        };

                        this.loadCoinInfo('');
                    }, err => {
                    });

                    this.ieoApi.getLastPrice('USDT', 'BTC').subscribe(res => {
                        if (res.success) {
                            this.btc_usd_price = res.price;
                        }
                    }, err => {
                    });

                    this.ieoApi.getLastPrice('USDT', 'ETH').subscribe(res => {
                        if (res.success) {
                            this.eth_usd_price = res.price;
                        }
                    }, err => {
                    });
                }
            }
        }, err=>{

        });
    }

    loadCoinInfo(before) {
        if (before != this.selCoin) {
            this.loadCoinLinks();
            this.loadCoinBonus();
        }
        this.loadStatistics();
        this.loadTradeHistory();

        if (this.selMarketCoin == 'TL') {
            this.buy.price = this.validate.round(this.selPair.price * this.usd_tl_price, 8);
        } else if (this.selMarketCoin == 'BTC') {
            this.buy.price = this.validate.round(this.selPair.price / this.btc_usd_price, 8);
        } else if (this.selMarketCoin == 'ETH') {
            this.buy.price = this.validate.round(this.selPair.price / this.eth_usd_price, 8);
        } else if (this.selMarketCoin == 'USD') {
            this.buy.price = this.selPair.price;
        }

        if (this.selPair.video_url != '' && this.selPair.video_url != null) {
            $('#video').html('<iframe width="100%" height="100%" src="' + this.selPair.video_url + '" frameborder="0" allowfullscreen></iframe>');
        } else {
            $('#video').html('<div class="text-center" style="width:100%;height:300px"><img width="100%" height="100%" src="assets/images/ico/'+this.selPair.coin+'-BANNER.png"></div>');
        }
    }
    onClickPair(pair) {
        if (pair.coin == this.selCoin && pair.market_coin == this.selMarketCoin) {
            return;
        }

        this.selMarketCoin = pair.market_coin;
        let beforeSel = this.selCoin;
        this.selCoin = pair.coin;
        this.selPair = pair;

        this.buy.amount = 0;
        this.calcTotal(null);

        this.loadCoinInfo(beforeSel);
    }

    onSelectMarketCoin(marketCoin) {
        this.activeMarketCoin = marketCoin;

        this.filterCoin();
    }

    onFilter() {
        this.filterCoin();
    }

    onSortTable(header) {
        if (this.sortInfo[this.activeMarketCoin].sortColumn == header && this.sortInfo[this.activeMarketCoin].sortOrder == 'desc') {
            this.sortInfo[this.activeMarketCoin].sortOrder = 'asc';
        } else {
            this.sortInfo[this.activeMarketCoin].sortOrder = 'desc';
        }

        this.sortInfo[this.activeMarketCoin].sortColumn = header;

        this.filterCoin();
    }

    filterCoin() {
        this.pairs = this.all_pairs[this.activeMarketCoin];
        this.pairs = this.pairs.filter(coin => coin.coin.toLowerCase().indexOf(this.filtertxt.toLowerCase()) !== -1 || coin.name.toLowerCase().indexOf(this.filtertxt.toLowerCase()) !== -1);

        if (this.sortInfo[this.activeMarketCoin].sortColumn != '') {
            let _parent = this;
            this.pairs = this.pairs.sort(function (r1, r2) {
                let r1v = r1[_parent.sortInfo[_parent.activeMarketCoin].sortColumn];
                let r2v = r2[_parent.sortInfo[_parent.activeMarketCoin].sortColumn];

                if (!isNaN(r1v)) r1v = Number(r1v);
                if (!isNaN(r2v)) r2v = Number(r2v);
                if (_parent.sortInfo[_parent.activeMarketCoin].sortOrder == 'asc') {
                    return r1v > r2v ? 1 : (r1v == r2v ? 0 : -1);
                } else {
                    return r1v < r2v ? 1 : (r1v == r2v ? 0 : -1);
                }
            });
        }
    }

    loadCoinLinks() {
        this.ieoApi.getCoinLinks({
            coin: this.selCoin
        }).subscribe(res => {
            if (res.success) {
                this.coinLinks = res.data;
            }
        }, err=>{

        });
    }

    loadCoinBonus() {
        this.ieoApi.getCoinBonus({
            coin: this.selCoin
        }).subscribe(res => {
            if (res.success) {
                this.bonus = res.data;
            }
        }, err=>{

        });
    }

    loadStatistics() {
        this.ieoApi.getStatistics({
            coin: this.selCoin
        }).subscribe(res => {
            if (res.success) {
                this.ieoSetting.ieo_amount = res.data.ieo_amount;

                // let value = this.selPair.target_cap.replace(/[^0-9]/g, '');

                $('.progress-inner').width(this.ieoSetting.ieo_amount / this.selPair.target_amount * 100 + '%');
            }
        }, err=>{

        });
    }

    loadTradeHistory() {
        this.ieoApi.getTradeHistory({
            coin: this.selCoin,
            market_coin: this.selMarketCoin
        }).subscribe(res => {
            if (res.success) {
                this.history = res.data;
            }
        }, err=>{

        });
    }

    calcTotal(e) {
        this.buy.marketcoin_total = this.buy.price * this.buy.amount;
        this.buy.usd_total = this.selPair.price * this.buy.amount;
    }

    buyToken() {
        if (this.buy.amount <= 0) {
            this.notify.showNotification('Warning', 'Please enter amount to buy', 'warning');
            return;
        }

        this.settings.loading = true;
        this.ieoApi.buyToken({
            coin: this.selCoin,
            market_coin: this.selMarketCoin,
            amount: this.buy.amount,
            price: this.buy.price
        }).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Successfully', 'You bought successfully...',  'success');
                this.balance.getCoinBalance(this.selCoin);
                this.balance.getCoinBalance(this.selMarketCoin);
                this.loadStatistics();
                this.loadTradeHistory();
            } else {
                this.notify.showNotification('Warning', res.error,  'warning');
            }
        }, err=> {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Error! Please try again later...',  'error');
        });
    }

    ngAfterViewInit() {
        $(function () {
            $(".preloader").fadeOut();
        });
        $(function () {
            (<any>$('[data-toggle="tooltip"]')).tooltip();
        });
    }

    ngOnDestroy() {
    }
}
