import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {Router} from "@angular/router";
import {SettingsService} from "../../../services/settings/settings.service";
import {IcoApi} from "../../../services/icoApi.service";
import {Notifications} from "../../../services/notifications.service";
import {BalanceService} from "../../../services/balance.service";

declare var swal: any;
declare var $: any;
declare var particlesJS: any;

@Component({
    templateUrl: './laarico.component.html',
    styleUrls: ['./laarico.component.scss']
})
export class LAARICOComponent implements OnInit, AfterViewInit, OnDestroy {
    icoSetting: any = {};
    remain: any = {
        day: 0,
        hour: 0,
        min: 0,
        sec: 0
    };

    timerID: any;
    ico_end: any;

    usd_price = 0;
    btc_price = 0;
    eth_price = 0;
    tl_price = 0;

    selBuyCoin = '';
    sel_price = 0;
    laarAmount = 0;
    constructor(public router: Router,
                public settings: SettingsService,
                public balance: BalanceService,
                public icoApi: IcoApi,
                public notify: Notifications
    ) {

    }

    ngOnInit() {
        this.icoApi.getLAARSetting({}).subscribe(res => {
            if (res.success) {
                this.icoSetting = res.data;
                this.ico_end = new Date(this.icoSetting.end_date);

                this.usd_price = this.icoSetting.usd_price;
                this.loadStatistics();
                this.loadBTCPrice();
                this.loadETHPrice();
                var _parent = this;
                this.timerID = setInterval(function () {
                    var now: any = new Date();
                    var secs = Math.round((_parent.ico_end - now) / 1000);

                    _parent.remain.day = Math.floor(secs / (60 * 60 * 24));
                    _parent.remain.hour = Math.floor((secs % (60 * 60 * 24)) / (60 * 60));
                    _parent.remain.min = Math.floor(((secs % (60 * 60 * 24)) % (60 * 60)) / 60);
                    _parent.remain.sec = secs % 60;
                }, 1000);
            }
        }, err=> {

        });
    }

    loadBTCPrice() {
        this.icoApi.getLastPrice('USDT', 'BTC').subscribe(res => {
            if (res.success) {
                this.btc_price = this.usd_price / res.price;

                this.icoApi.getLastPrice('TL', 'BTC').subscribe(res => {
                    if (res.success) {
                        this.tl_price = this.btc_price * res.price;
                    }
                }, err => {
                })
            } else {
            }
        }, err => {
        })
    }

    loadETHPrice() {
        this.icoApi.getLastPrice('USDT', 'ETH').subscribe(res => {
            if (res.success) {
                this.eth_price = this.usd_price / res.price;
            } else {
            }
        }, err => {
        })
    }

    loadStatistics() {
        this.icoApi.getLAARStatistics({}).subscribe(res => {
            if (res.success) {
                this.icoSetting.ico_amount = res.data.ico_amount;
                this.icoSetting.btc_amount = res.data.btc_amount;
                this.icoSetting.eth_amount = res.data.eth_amount;
                this.icoSetting.tl_amount = res.data.tl_amount;
                this.icoSetting.usd_amount = res.data.usd_amount;

                $('.progress-inner').width(this.icoSetting.ico_amount / this.icoSetting.total_cap * 100 + '%');
            }
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
        if (this.timerID) {
            clearInterval(this.timerID);
        }
    }

    onClickBalance(coin) {
        if (this.selBuyCoin == coin) {
            let balance = this.settings.getBalance(coin);
            if (coin == 'BTC') {
                this.laarAmount = balance / this.btc_price;
            } else if (coin == 'ETH') {
                this.sel_price = balance / this.eth_price;
            } else if (coin == 'TL') {
                this.sel_price = balance / this.tl_price;
            } else if (coin == 'USD') {
                this.sel_price = balance / this.usd_price;
            }
        }
    }

    onClickBuyMenu(coin) {
        if (this.selBuyCoin == coin) {
            this.selBuyCoin = '';
        } else {
            this.selBuyCoin = coin;
            if (coin == 'BTC') {
                this.sel_price = this.btc_price;
            } else if (coin == 'ETH') {
                this.sel_price = this.eth_price;
            } else if (coin == 'TL') {
                this.sel_price = this.tl_price;
            } else if (coin == 'USD') {
                this.sel_price = this.usd_price;
            }
        }
    }

    onBuyLAAR() {
        if (this.laarAmount < 0) {
            this.notify.showNotification('Warning', 'Please enter amount to buy', 'warning');
            return;
        }

        if (Number(this.laarAmount) < Number(this.icoSetting.min_amount) || isNaN(this.laarAmount)) {
            this.notify.showNotification('Warning', 'You cannot buy LAAR less than ' + this.icoSetting.min_amount + ' ', 'warning');
            return;
        }

        this.settings.loading = true;
        this.icoApi.buyLAAR({
            coin: this.selBuyCoin,
            amount: this.laarAmount,
            price: this.sel_price
        }).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Successfully', 'You bought LAAR successfully...',  'success');
                this.balance.getCoinBalance('LAAR');
                this.balance.getCoinBalance(this.selBuyCoin);
                this.loadStatistics();
            } else {
                this.notify.showNotification('Warning', res.error,  'warning');
            }
        }, err=> {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Error! Please try again later...',  'error');
        });
    }
}
