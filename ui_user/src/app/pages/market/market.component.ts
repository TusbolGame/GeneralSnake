import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {SettingsService} from "../../services/settings/settings.service";
import {BalanceService} from "../../services/balance.service";
import {StockChart, Highcharts} from "angular-highcharts";
import {MarketApi} from "../../services/marketApi.service";
import {Validate} from "../../services/validate.service";
import {Notifications} from "../../services/notifications.service";
import {Subject} from "rxjs";
import {takeUntil} from "rxjs/operators";
import {PusherService} from "../../services/pusher.service";
import {TranslateService} from "@ngx-translate/core";

declare const $: any;
declare const swal: any;

@Component({
    templateUrl: './market.component.html',
    styleUrls: ['./market.component.scss']
})
export class MarketComponent implements OnInit, AfterViewInit, OnDestroy {
    all_pairs: any = {};

    pairs: any = [];

    buy: any = {};
    sell: any = {};

    stockChart: any;

    activeMarketCoin = 'TL';
    selMarketCoin = 'TL';
    selCoin = 'BTC';
    selPair: any = {
        places: 6
    };

    sellOrders: any = [];
    buyOrders: any = [];
    tradeHistory: any = [];

    pastOrders: any = [];
    pendingOrders: any = [];

    chartTitle = "";
    private ngUnsubscribe = new Subject();

    filtertxt = "";

    sortInfo: any = {
        TL : {sortColumn: 'coin', sortOrder: 'asc'},
        BTC : {sortColumn: 'coin', sortOrder: 'asc'},
        ETH : {sortColumn: 'coin', sortOrder: 'asc'},
        USD : {sortColumn: 'coin', sortOrder: 'asc'},
        EURO : {sortColumn: 'coin', sortOrder: 'asc'},
        USDT : {sortColumn: 'coin', sortOrder: 'asc'},
        MISC : {sortColumn: 'coin', sortOrder: 'asc'},
        USC : {sortColumn: 'coin', sortOrder: 'asc'},
    };

    lastColumn = 'change';

    constructor(public marketApi: MarketApi,
                public settings: SettingsService,
                public balance: BalanceService,
                public validate: Validate,
                public notify: Notifications,
                public pusher: PusherService,
                public translate: TranslateService) {

    }

    ngOnInit() {
        if (window.localStorage.getItem('coin_pair') && window.localStorage.getItem('coin_pair') != '') {
            let coin_pair = window.localStorage.getItem('coin_pair');
            this.selCoin = coin_pair.split('-')[0];
            this.selMarketCoin = coin_pair.split('-')[1];
            this.activeMarketCoin = this.selMarketCoin;
            if (this.activeMarketCoin != 'TL' && this.activeMarketCoin != 'BTC' && this.activeMarketCoin != 'ETH' && this.activeMarketCoin != 'USD' && this.activeMarketCoin != 'USDT' && this.activeMarketCoin != 'EURO' && this.activeMarketCoin != 'USC') {
                this.activeMarketCoin = 'MISC';
            }
        }

        this.translate.get('MARKET.MARKET_HISTORY', {}).subscribe((res: string) => {
            this.chartTitle = res;
        });

        this.marketApi.getUserCoinPairInfo({}).subscribe(res => {
            if (res.success) {
                this.all_pairs.TL = res.data.filter(pair => pair.market_coin === 'TL');
                this.all_pairs.BTC = res.data.filter(pair => pair.market_coin === 'BTC');
                this.all_pairs.ETH = res.data.filter(pair => pair.market_coin === 'ETH');
                this.all_pairs.USD = res.data.filter(pair => pair.market_coin === 'USD');
                this.all_pairs.EURO = res.data.filter(pair => pair.market_coin === 'EURO');
                this.all_pairs.USDT = res.data.filter(pair => pair.market_coin === 'USDT');
                this.all_pairs.USC = res.data.filter(pair => pair.market_coin === 'USC');
                this.all_pairs.MISC = res.data.filter(pair => pair.market_coin != 'TL' && pair.market_coin != 'BTC' && pair.market_coin != 'ETH' && pair.market_coin != 'USD' && pair.market_coin != 'EURO' && pair.market_coin != 'USDT' && pair.market_coin != 'USC');

                this.filterCoin();
                this.selPair = res.data.filter(pair => pair.coin === this.selCoin)[0];
            }
        });

        this.loadBuyOrder();
        this.loadSellOrder();
        this.loadMarketHistory();
        this.loadPendingOrders();
        this.loadPastOrders();

        this.pusher.getOrderEvent()
            .pipe(takeUntil(this.ngUnsubscribe))
            .subscribe(data => {
                this.onOrderEvent(data);
            });

// Apply the theme
        Highcharts.setOptions({
            colors: ['#f45b5b', '#8085e9', '#8d4654', '#7798BF', '#aaeeee',
                '#ff0066', '#eeaaee', '#55BF3B', '#DF5353', '#7798BF', '#aaeeee'],
            chart: {
                backgroundColor: null
            },
            tooltip: {
                borderWidth: 0
            },
        });

        this.loadChart();
    }

    ngOnDestroy() {
        this.ngUnsubscribe.next();
        this.ngUnsubscribe.complete();
    }

    ngAfterViewInit() {

    }

    onOrderEvent(data) {
        if (data.last_price && data.last_price != '' && data.last_price > 0) {
            let pair;
            if (data.marketCoin != 'TL' && data.marketCoin != 'BTC' && data.marketCoin != 'ETH' &&
                data.marketCoin != 'USD' && data.marketCoin != 'USDT' && data.marketCoin != 'EURO' && data.marketCoin != 'USC' ) {
                pair = this.all_pairs.MISC.filter(pair => pair.coin == data.coin && pair.market_coin == data.marketCoin)[0];
            } else {
                pair = this.all_pairs[data.marketCoin].filter(pair => pair.coin == data.coin)[0];
            }
            pair.last_price = data.last_price;
            pair.change = pair.first_price > 0 ? (pair.last_price - pair.first_price) * 100 / pair.first_price : 0;
            if (pair.last_price > pair.high) {
                pair.high = pair.last_price;
            }
            if (pair.last_price < pair.low) {
                pair.low = pair.last_price;
            }
            pair.volume = Number(pair.volume) + (Number(data.volume) > 0 ? Number(data.volume) : 0);

            if (this.selCoin == data.coin && this.selMarketCoin == data.marketCoin) {
                if (data.trades.length && data.trades.length > 0) {
                    for (let i = 0; i < data.trades.length; i ++ ) {
                        if (data.trades[i].type == 0) {
                            this.tradeHistory.splice(0, 0, {
                                rate: data.trades[i].rate,
                                datetime: data.datetime,
                                exchange_type: data.trades[i].type,
                                dest_amount: data.trades[i].amount,
                                fee: 0
                            });
                        } else if (data.trades[i].type == 1) {
                            this.tradeHistory.splice(0, 0, {
                                rate: data.trades[i].rate,
                                datetime: data.datetime,
                                exchange_type: data.trades[i].type,
                                src_amount: data.trades[i].amount
                            });
                        }
                    }
                }
            }

            // this.filterCoin();
        }

        if (this.selCoin == data.coin && this.selMarketCoin == data.marketCoin) {
            if (data.users.length && data.users.length > 0 && data.users.includes(this.settings.getUserSetting('id'))) {
                this.loadPendingOrders();
                this.loadPastOrders();
            }

            if (data.rates.length && data.rates.length > 0) {
                for (let i = 0; i < data.rates.length; i ++) {
                    let order = this.sellOrders.filter(order => Number(order.rate) == Number(data.rates[i].rate));
                    if (order.length > 0 && Number(data.rates[i].sellAmount) <= 0) {
                        this.sellOrders = this.sellOrders.filter(order => Number(order.rate) != Number(data.rates[i].rate));
                    } else if (order.length > 0 && Number(data.rates[i].sellAmount) > 0) {
                        order[0].amount = data.rates[i].sellAmount;
                    } else if (order.length == 0 && Number(data.rates[i].sellAmount) > 0) {
                        this.sellOrders.push({
                            rate: data.rates[i].rate,
                            amount: data.rates[i].sellAmount
                        });
                        this.sellOrders = this.sellOrders.sort(function (r1, r2) {
                            return Number(r1.rate) > Number(r2.rate) ? 1 : -1;
                        });
                    }

                    order = this.buyOrders.filter(order => Number(order.rate) == Number(data.rates[i].rate));
                    if (order.length > 0 && Number(data.rates[i].buyAmount) <= 0) {
                        this.buyOrders = this.buyOrders.filter(order => Number(order.rate) != Number(data.rates[i].rate));
                    } else if (order.length > 0 && Number(data.rates[i].buyAmount) > 0) {
                        order[0].amount = data.rates[i].buyAmount;
                    } else if (order.length == 0 && Number(data.rates[i].buyAmount) > 0) {
                        this.buyOrders.push({
                            rate: data.rates[i].rate,
                            amount: data.rates[i].buyAmount
                        });
                        this.buyOrders = this.buyOrders.sort(function (r1, r2) {
                            return Number(r1.rate) < Number(r2.rate) ? 1 : -1;
                        });
                    }
                }
            }
        }
    }

    loadChart() {
        var _parent = this;
        this.marketApi.getChartData(this.selCoin, this.selMarketCoin, {}).subscribe(res => {
            if (res.success) {
                // split the data set into ohlc and volume
                let ohlc = [],
                    volume = [],
                    dataLength = res.data.length,
                    // set the allowed units for data grouping
                    groupingUnits = [[
                        'minute',                         // unit name
                        [1, 2, 3, 4, 6]                   // allowed multiples
                    ], [
                        'hour',
                        [1, 2, 3, 4, 5, 6, 7]
                    ]],

                    i = 0;

                for (i; i < res.data.length; i += 1) {
                    ohlc.push([
                        Number(res.data[i].date), // the date
                        Number(res.data[i].open), // open
                        Number(res.data[i].high), // high
                        Number(res.data[i].low), // low
                        Number(res.data[i].close) // close
                    ]);

                    volume.push([
                        Number(res.data[i].date), // the date
                        Number(res.data[i].volume) // the volume
                    ]);
                }

                let options: any = {
                    rangeSelector: {
                        selected: 5,
                        buttons: [{
                            type: 'minute',
                            count: 10,
                            text: '10m',
                        }, {
                            type: 'minute',
                            count: 20,
                            text: '20m'
                        }, {
                            type: 'hour',
                            count: 1,
                            text: '1h'
                        }, {
                            type: 'hour',
                            count: 12,
                            text: '12h'
                        }, {
                            type: 'day',
                            count: 1,
                            text: '1d'
                        }, {
                            type: 'day',
                            count: 7,
                            text: '1w'
                        }, {
                            type: 'all',
                            text: 'All'
                        }]
                    },

                    title: {
                        text: _parent.chartTitle
                    },

                    yAxis: [{
                        labels: {
                            align: 'right',
                            x: -3
                        },
                        title: {
                            text: 'Price'
                        },
                        height: '80%',
                        lineWidth: 2,
                        resize: {
                            enabled: true
                        }
                    }, {
                        labels: {
                            align: 'right',
                            x: -3
                        },
                        title: {
                            text: 'Volume'
                        },
                        top: '80%',
                        height: '20%',
                        offset: 0,
                        lineWidth: 2
                    }],

                    tooltip: {
                        split: true
                    },

                    series: [{
                        type: 'candlestick',
                        name: 'Exchange Price',
                        data: ohlc,
                        dataGrouping: {
                            units: groupingUnits
                        }
                    }, {
                        type: 'column',
                        name: 'Volume',
                        data: volume,
                        yAxis: 1,
                        dataGrouping: {
                            units: groupingUnits
                        }
                    }]
                };
                // create the chart
                _parent.stockChart = new StockChart(options);

                let timerID = setInterval(function () {
                    if ($('.highcharts-credits').length > 0) {
                        $('.highcharts-credits').hide();
                        clearInterval(timerID);
                    }
                }, 100);
            }
        });

    }

    loadSellOrder() {
        this.marketApi.getSellOrders(this.selCoin, this.selMarketCoin, {}).subscribe(res => {
            if (res.success) {
                this.sellOrders = res.data;
            }
        });
    }

    loadBuyOrder() {
        this.marketApi.getBuyOrders(this.selCoin, this.selMarketCoin, {}).subscribe(res => {
            if (res.success) {
                this.buyOrders = res.data;
            }
        });
    }

    loadMarketHistory() {
        this.marketApi.getLastOrders(this.selCoin, this.selMarketCoin, {}).subscribe(res => {
            if (res.success) {
                this.tradeHistory = res.data;
            }
        });
    }

    loadPastOrders() {
        this.marketApi.getUserAllPastOrders({}).subscribe(res => {
            if (res.success) {
                this.pastOrders = res.data;
            }
        });
    }

    loadPendingOrders() {
        this.marketApi.getUserAllPendingOrders({}).subscribe(res => {
            if (res.success) {
                this.pendingOrders = res.data;
            }
        });
    }

    newBuyOrder() {
        if (!this.buy.rate || !this.buy.amount) {
            return;
        }

        if (this.buy.rate == '' || this.buy.amount == '') {
            return;
        }
        if (this.buy.rate == 0 || this.buy.amount == 0) {
            return;
        }

        this.settings.loading = true;
        this.marketApi.makeBuyOrder(this.selCoin, this.selMarketCoin, this.buy).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Success', 'Your order was posted successfully.', 'success');
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Failed. Please try again.', 'warning');
        });
    }

    newSellOrder() {
        if (!this.sell.rate || !this.sell.amount) {
            return;
        }

        if (this.sell.rate == '' || this.sell.amount == '') {
            return;
        }
        if (this.sell.rate == 0 || this.sell.amount == 0) {
            return;
        }

        this.settings.loading = true;
        this.marketApi.makeSellOrder(this.selCoin, this.selMarketCoin, this.sell).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Success', 'Your order was posted successfully.', 'success');
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Failed. Please try again.', 'warning');
        });
    }

    cancelOrder(id) {
        let _parent = this;
        swal({
            title: 'Are you sure?',
            text: 'Your order will be deleted permanently.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn btn-success',
            cancelButtonClass: 'btn btn-danger',
            confirmButtonText: 'Yes',
            buttonsStyling: false
        }, function () {
            _parent.marketApi.cancelOrder({
                id: id
            }).subscribe(res => {
                if (res.success) {
                    _parent.notify.showNotification('Success', 'Your order was canceled successfully.', 'success');

                    _parent.balance.getCoinBalance(_parent.selCoin);
                    _parent.balance.getCoinBalance(_parent.selMarketCoin);
                    _parent.loadPendingOrders();

                } else {
                    _parent.notify.showNotification('Warning', res.error, 'warning');
                }
            });
        });
    }

    onClickPair(pair) {
        if (pair.coin == this.selCoin && pair.market_coin == this.selMarketCoin) {
            return;
        }

        this.buy = {};
        this.sell = {};

        this.selMarketCoin = pair.market_coin;
        this.selCoin = pair.coin;
        this.selPair = pair;

        this.sell.rate = this.validate.round(this.selPair.last_price, this.selPair.places);
        this.buy.rate = this.validate.round(this.selPair.last_price, this.selPair.places);

        this.loadSellOrder();
        this.loadBuyOrder();
        this.loadMarketHistory();

        this.loadChart();
        window.localStorage.setItem('coin_pair', this.selCoin + '-' + this.selMarketCoin);
    }

    onSelectMarketCoin(marketCoin) {
        this.activeMarketCoin = marketCoin;

        this.filterCoin();
        // this.pairs = this.all_pairs[marketCoin];
    }

    calcSellTotal(e) {
        if (this.sell.rate > 0 && this.sell.amount > 0) {
            this.sell.total = this.validate.round(this.sell.rate * this.sell.amount, this.selPair.places);
        }
    }

    calcSellRate(e) {
        if (this.sell.rate > 0) {
            this.sell.amount = this.validate.floor(this.sell.total / this.sell.rate, 8);
        } else if (this.sell.amount > 0) {
            this.sell.rate = this.validate.floor(this.sell.total / this.sell.amount, this.selPair.places);
        }
    }

    calcSellPercent(v) {
        this.sell.amount = this.validate.floor(this.settings.getBalance(this.selCoin) * v, 8);
        this.calcSellTotal(this.sell.amount);
    }

    calcBuyTotal(e) {
        if (this.buy.rate > 0 && this.buy.amount > 0) {
            this.buy.total = this.validate.round(this.buy.rate * this.buy.amount, this.selPair.places);
        }
    }

    calcBuyRate(e) {
        if (this.buy.rate > 0) {
            this.buy.amount = this.validate.floor(this.buy.total / this.buy.rate, 8);
        } else if (this.buy.amount > 0) {
            this.buy.rate = this.validate.floor(this.buy.total / this.buy.amount, this.selPair.places);
        }
    }

    calcBuyPercent(v) {
        this.buy.total = this.validate.floor(this.settings.getBalance(this.selMarketCoin) * v, 8);
        this.calcBuyRate(this.buy.total);
    }

    setBuyRate(rate, amount) {
        this.buy.rate = this.validate.round(rate, this.selPair.places);
        let orders = this.sellOrders.filter(order => Number(order.rate) <= Number(rate));

        let total_amount = 0;
        let total_value = 0;
        for (let i = 0; i < orders.length; i ++) {
            total_amount += Number(orders[i].amount);
            total_value += Number(orders[i].amount) * Number(orders[i].rate);
        }

        this.buy.amount = this.validate.round(total_amount, 8);
        this.buy.total = this.validate.round(total_value, 8);
    }

    setRate(rate) {
        this.sell.rate = this.validate.round(rate, this.selPair.places);
        this.buy.rate = this.validate.round(rate, this.selPair.places);
    }

    setSellRate(rate, amount) {
        this.sell.rate = this.validate.round(rate, this.selPair.places);
        let orders = this.buyOrders.filter(order => Number(order.rate) >= Number(rate));

        let total_amount = 0;
        let total_value = 0;
        for (let i = 0; i < orders.length; i ++) {
            total_amount += Number(orders[i].amount);
            total_value += Number(orders[i].amount) * Number(orders[i].rate);
        }
        this.sell.amount = this.validate.round(total_amount, 8);
        this.sell.total = this.validate.round(total_value, 8);
    }

    add(num1, num2) {
        return Number(num1) + Number(num2);
    }

    filterCoin() {
        this.pairs = this.all_pairs[this.activeMarketCoin];
        this.pairs = this.pairs.filter(coin => coin.coin.toLowerCase().indexOf(this.filtertxt.toLowerCase()) !== -1 || coin.fullname.toLowerCase().indexOf(this.filtertxt.toLowerCase()) !== -1);

        if (this.sortInfo[this.activeMarketCoin].sortColumn != '') {
            let _parent = this;
            this.pairs = this.pairs.sort(function (r1, r2) {
                let r1v = r1[_parent.sortInfo[_parent.activeMarketCoin].sortColumn];
                let r2v = r2[_parent.sortInfo[_parent.activeMarketCoin].sortColumn];

                if (_parent.sortInfo[_parent.activeMarketCoin].sortColumn == 'volume') {
                    r1v = r1v * r1['last_price'];
                    r2v = r2v * r2['last_price'];
                }

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
}