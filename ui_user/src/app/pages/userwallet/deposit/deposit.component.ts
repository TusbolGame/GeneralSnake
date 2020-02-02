import {Component, AfterViewInit, OnInit} from '@angular/core';
import {SettingsService} from "../../../services/settings/settings.service";
import {Notifications} from "../../../services/notifications.service";
import {BalanceService} from "../../../services/balance.service";
import {CoinApi} from "../../../services/coinApi.service";

declare const swal: any;
declare const ripple: any;
declare const bchaddr: any;
declare const $: any;

@Component({
    templateUrl: './deposit.component.html',
    styleUrls: ['./deposit.component.scss']
})
export class DepositComponent implements OnInit, AfterViewInit {
    selCoin = 'BTC';
    selCoinAddress = '';
    selCoinInfo: any = {
        fullname: ''
    };
    coins: any = [];

    coinAddress: any = {};
    XINPublicKey = '';

    depositTx: any = [];

    mainWallet: any = {
        address: '',
        minimum_amount: 0
    };

    constructor(public coinApi: CoinApi,
                public settings: SettingsService,
                public notify: Notifications,
                public balance: BalanceService) {

    }

    ngOnInit() {
        if (window.localStorage.getItem('deposit_coin') && window.localStorage.getItem('deposit_coin') != '') {
            this.selCoin = window.localStorage.getItem('deposit_coin');
        }

        this.coinApi.getCoinAddress({}).subscribe(res => {
            if (res.success) {
                this.coinAddress = res.data;

                this.selCoinAddress = this.getAddress(this.selCoin);
                if (this.selCoin == 'XIN') {
                    this.coinApi.getXINPublicKey({
                        address: this.selCoinAddress
                    }).subscribe(res => {
                        if (res.success) {
                            this.XINPublicKey = res.publicKey;
                        }
                    });
                }
            }
        });

        this.coinApi.getCoin({
            only_coin: true
        }).subscribe(res => {
            if (res.success) {
                this.coins = res.data;
                this.selCoinInfo = this.coins.filter(coin => coin.coin === this.selCoin)[0];
            }
        }, err => {

        });

        this.coinApi.getXRPMainWallet({}).subscribe( res => {
            if (res.success) {
                this.mainWallet.address = res.address;
                this.mainWallet.minimum_amount = res.minimum_amount;
            }
        }, err => {

        });

        this.getDepositList();
    }

    getAddress(coin) {
        if (coin == 'ETN' && this.coinAddress['etn_paymentid'] && this.coinAddress['etn_paymentid'] != '') {
            return this.coinAddress['etn_paymentid'];
        } else if (coin == 'ETN') {
            return '';
        }

        if (coin == 'CCX' && this.coinAddress['ccx_paymentid'] && this.coinAddress['ccx_paymentid'] != '') {
            return this.coinAddress['ccx_paymentid'];
        } else if (coin == 'CCX') {
            return '';
        }

        if (this.selCoinInfo.is_erc20 == 1) {
            coin = 'ETH';
        }
        if (coin == 'CVN' || coin == 'DEEX') {
            coin = 'BTS';
        }
        if (coin == 'VEX') {
            coin = 'ACT';
        }
        if (coin == 'CLB' || coin == 'ADMN') {
            coin = 'WAVES';
        }
        if (coin == 'ONG') {
            coin = 'ONT';
        }
        if (coin == 'IGG' || coin == 'BTT') {
            coin = 'TRX';
        }

        let index = coin.toLowerCase() + '_address';

        if (this.coinAddress && this.coinAddress[index] && this.coinAddress[index] != '') {
            if (coin == 'BCH') {
                return bchaddr.toLegacyAddress(this.coinAddress[index]);
            }
            return this.coinAddress[index];
        } else {
            return '';
        }
    }

    setAddress(coin, address) {
        if (this.selCoinInfo.is_erc20 == 1) {
            coin = 'ETH';
        }
        if (coin == 'CVN' || coin == 'DEEX') {
            coin = 'BTS';
        }
        if (coin == 'VEX') {
            coin = 'ACT';
        }
        if (coin == 'CLB' || coin == 'ADMN') {
            coin = 'WAVES';
        }
        if (coin == 'ONG') {
            coin = 'ONT';
        }
        if (coin == 'IGG' || coin == 'BTT') {
            coin = 'TRX';
        }

        let index = coin.toLowerCase() + '_address';

        if (coin == 'ETN') {
            this.coinAddress['etn_paymentid'] = address;
        } else if (coin == 'CCX') {
            this.coinAddress['ccx_paymentid'] = address;
        } else {
            this.coinAddress[index] = address;
        }
    }

    ngAfterViewInit() {
        $('.select2').select2();

        let _parent = this;
        $('.select2').on('select2:select', function (e) {
            _parent.onClickCoin(e.params.data.id);
        });
    }

    onClickCoin(coin) {
        if (coin == this.selCoin) {
            return;
        }

        this.selCoin = coin;
        this.selCoinInfo = this.coins.filter(coin => coin.coin == this.selCoin)[0];
        this.selCoinAddress = this.getAddress(this.selCoin);

        this.getDepositList();

        window.localStorage.setItem('deposit_coin', this.selCoin);
    }

    createWallet() {
        this.settings.loading = true;
        this.coinApi.createWallet(this.selCoin, {}).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Success', 'Your wallet was created successfully.', 'success');

                this.setAddress(this.selCoin, res.address);
                this.selCoinAddress = this.getAddress(this.selCoin);
                if (this.selCoin == 'XIN') {
                    this.XINPublicKey = res.publicKey;
                }
            } else {
                this.notify.showNotification('Error', res.error, 'error');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Please try again.', 'error');
        });
    }

    getDepositList() {
        this.coinApi.getDepositHistory(this.selCoin, {}).subscribe(res => {
            if (res.success) {
                this.depositTx = res.data;
            } else {

            }
        }, err => {
        });
    }

    copyAddress(id) {
        $('#' + id).select();
        document.execCommand('copy');

        window.getSelection().removeAllRanges();
    }

    refreshDepositList() {
        this.balance.getCoinBalance(this.selCoin);

        this.getDepositList();
    }
}