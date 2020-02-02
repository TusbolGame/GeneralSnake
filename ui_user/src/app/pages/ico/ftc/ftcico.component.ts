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
    templateUrl: './ftcico.component.html',
    styleUrls: ['./ftcico.component.scss']
})
export class FTCICOComponent implements OnInit, AfterViewInit, OnDestroy {
    icoSetting: any = {};
    remain: any = {
        day: 0,
        hour: 0,
        min: 0,
        sec: 0
    };

    timerID: any;
    curPhase = 1;
    ico_end: any;

    btc_price = 0;
    eth_price = 0;
    xrp_price = 0;

    selBuyCoin = '';
    sel_price = 0;
    coinAmount = 0;
    constructor(public router: Router,
                public settings: SettingsService,
                public balance: BalanceService,
                public icoApi: IcoApi,
                public notify: Notifications
    ) {

    }

    ngOnInit() {
        this.icoApi.getFTCSetting({}).subscribe(res => {
            if (res.success) {
                this.icoSetting = res.data;
                this.curPhase = this.icoSetting.current_phase;
                if (this.curPhase == 1) {
                    this.ico_end = new Date(this.icoSetting.phase1_date);
                    this.btc_price = this.icoSetting.phase1_btc;
                    this.eth_price = this.icoSetting.phase1_eth;
                    this.xrp_price = this.icoSetting.phase1_xrp;
                } else {
                    this.ico_end = new Date(this.icoSetting.phase2_date);
                    this.btc_price = this.icoSetting.phase2_btc;
                    this.eth_price = this.icoSetting.phase2_eth;
                    this.xrp_price = this.icoSetting.phase2_xrp;
                }

                this.loadStatistics();
                var _parent = this;
                this.timerID = setInterval(function () {
                    var now: any = new Date();
                    var secs = Math.round((_parent.ico_end - now) / 1000);

                    _parent.remain.day = Math.floor(secs / (60 * 60 * 24));
                    _parent.remain.hr = Math.floor((secs % (60 * 60 * 24)) / (60 * 60));
                    _parent.remain.min = Math.floor(((secs % (60 * 60 * 24)) % (60 * 60)) / 60);
                    _parent.remain.sec = secs % 60;
                }, 1000);
            }
        }, err=> {

        });
    }

    loadStatistics() {
        this.icoApi.getFTCStatistics({}).subscribe(res => {
            if (res.success) {
                this.icoSetting.ico_amount = res.data.ico_amount;
                this.icoSetting.btc_amount = res.data.btc_amount;
                this.icoSetting.eth_amount = res.data.eth_amount;
                this.icoSetting.xrp_amount = res.data.xrp_amount;

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

        particlesJS.load('particles-js', 'assets/particles.json', function () {
            console.log('callback - particles.js config loaded');
        });
    }

    ngOnDestroy() {
        if (this.timerID) {
            clearInterval(this.timerID);
        }
    }

    onClickBalance(coin) {
        if (this.selBuyCoin == coin) {
            this.coinAmount = this.settings.getBalance(coin);
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
            } else if (coin == 'XRP') {
                this.sel_price = this.xrp_price;
            }
        }
    }

    onBuyFTC() {
        if (this.coinAmount < 0) {
            this.notify.showNotification('Warning', 'Please enter amount to buy', 'warning');
            return;
        }

        let ftc_amount = this.coinAmount / this.sel_price;

        if (ftc_amount < this.icoSetting.min_amount || isNaN(ftc_amount)) {
            this.notify.showNotification('Warning', 'You cannot buy FTC less than ' + this.icoSetting.min_amount + ' ', 'warning');
            return;
        }

        this.settings.loading = true;
        this.icoApi.buyFTC({
            coin: this.selBuyCoin,
            amount: this.coinAmount,
            price: this.sel_price
        }).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Successfully', 'You bought FTC successfully...',  'success');
                this.balance.getCoinBalance('FTC');
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
