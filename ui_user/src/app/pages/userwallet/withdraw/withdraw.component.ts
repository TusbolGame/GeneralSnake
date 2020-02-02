import {Component, AfterViewInit, OnInit} from '@angular/core';
import {SettingsService} from "../../../services/settings/settings.service";
import {Notifications} from "../../../services/notifications.service";
import {BalanceService} from "../../../services/balance.service";
import {CoinApi} from "../../../services/coinApi.service";
import {Validate} from "../../../services/validate.service";
import {Base58Service} from "../../../services/base58.service";

declare const swal: any;
declare const ripple: any;
declare const bchaddr: any;
declare const $: any;

@Component({
    templateUrl: './withdraw.component.html',
    styleUrls: ['./withdraw.component.scss']
})
export class WithdrawComponent implements OnInit, AfterViewInit {
    selCoin = 'BTC';
    selCoinInfo: any = {
        fullname: ''
    };
    coins: any = [];

    withdrawTx: any = [];
    showG2FModal = false;
    g2f_code = '';

    showSMSModal = false;
    sms_code = '';

    showEmailModal = false;
    email_code = '';

    withdraw: any = {
        to_amount: 0,
        use_tag: false,
        use_memo: false,
        use_paymentid: false,
        hexAddress: '',
        use_msg: false
    };

    mainWallet: any = {
        address: '',
        minimum_amount: 0
    };

    constructor(public coinApi: CoinApi,
                public settings: SettingsService,
                public notify: Notifications,
                public balance: BalanceService,
                public validate: Validate,
                public base58: Base58Service
    ) {

    }

    ngOnInit() {
        if (window.localStorage.getItem('withdraw_coin') && window.localStorage.getItem('withdraw_coin') != '') {
            this.selCoin = window.localStorage.getItem('withdraw_coin');
        }

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

        this.getWithdrawList();
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

        this.withdraw = {
            to_amount: 0,
            use_tag: false,
            use_memo: false,
            use_paymentid: false,
            use_msg: false
        };

        this.getWithdrawList();

        window.localStorage.setItem('withdraw_coin', this.selCoin);
    }

    getWithdrawList() {
        this.coinApi.getWithdrawHistory(this.selCoin, {}).subscribe(res => {
            if (res.success) {
                this.withdrawTx = res.data;
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

    refreshWithdrawList() {
        this.balance.getCoinBalance(this.selCoin);

        this.getWithdrawList();
    }

    withdrawAll() {
        this.withdraw.to_amount = this.settings.getBalance(this.selCoin);
    }

    submitWithdraw() {
        this.settings.loading = true;
        this.coinApi.requestWithdraw(this.selCoin, {
            address: this.withdraw.to_address,
            amount: this.withdraw.to_amount,
            destination_tag: this.withdraw.to_tag,
            memo: this.withdraw.memo,
            public_key: this.withdraw.public_key,
            paymentid: this.withdraw.paymentid,
            msg: this.withdraw.msg
        }).subscribe(res => {
            this.settings.loading = false;

            if (res.success) {
                this.notify.showNotification('Success', 'We have sent confirmation email for withdraw. Please check email and confirm your withdraw.', 'success');
                this.getWithdrawList();
                this.balance.getCoinBalance(this.selCoin);
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Warning', 'Please enter amount correctly.', 'warning');
        });
    }

    withdrawCoin() {
        if (this.settings.getUserSetting('account_verified') == 0) {
            this.notify.showNotification('Warning', 'You have to verify your account.', 'warning');
            return;
        }

        if (this.withdraw.to_address == null || this.withdraw.to_address == '') {
            this.notify.showNotification('Warning', 'Please enter Address', 'warning');
            return;
        }

        if (this.withdraw.to_amount - Number(this.selCoinInfo.withdraw_fee) < this.selCoinInfo.min_withdraw) {
            this.notify.showNotification('Warning', 'Please enter greater than minimual withdraw amount.', 'warning');
            return;
        }

        if (this.selCoin == 'XRP') {
            var UInt160 = ripple.UInt160;
            if (!UInt160.is_valid(this.withdraw.to_address)) {
                this.notify.showNotification('Warning', 'Address is invalid.', 'warning');
                return;
            }
            if (this.withdraw.to_amount < this.mainWallet.minimum_amount) {
                this.notify.showNotification('Warning', 'Minimal Withdraw amount is ' + this.mainWallet.minimum_amount + ' XRP.', 'warning');
                return;
            }
            if (this.withdraw.use_tag && (this.withdraw.to_tag == null || this.withdraw.to_tag == '')) {
                this.notify.showNotification('Warning', 'Please enter destination tag.', 'warning');
                return;
            }
        }

        if (this.selCoin == 'CVN' || this.selCoin == 'DEEX' || this.selCoin == 'SCR' || this.selCoin == 'BRAVO') {
            if (this.withdraw.use_memo && (this.withdraw.memo == null || this.withdraw.memo == '')) {
                this.notify.showNotification('Warning', 'Please input memo.', 'warning');
                return;
            }
        }

        if (this.selCoin == 'TRX' || this.selCoin == 'IGG' || this.selCoin == 'BTT') {
            let hexAddress = this.base58.decode(this.withdraw.to_address);

            if (hexAddress === false) {
                this.notify.showNotification('Warning', 'Wrong address', 'warning');
                return;
            }
        }

        if (this.selCoin == 'ETN') {
            if (this.withdraw.use_paymentid && (this.withdraw.paymentid == null || this.withdraw.paymentid == '')) {
                this.notify.showNotification('Warning', 'Please input payment id.', 'warning');
                return;
            }
        }

        if (this.selCoin == 'CCX') {
            if (this.withdraw.use_paymentid && (this.withdraw.paymentid == null || this.withdraw.paymentid == '')) {
                this.notify.showNotification('Warning', 'Please input payment id.', 'warning');
                return;
            }
        }

        if (this.selCoin == 'MPG') {
            if (this.withdraw.use_msg && (this.withdraw.use_msg == null || this.withdraw.use_msg == '')) {
                this.notify.showNotification('Warning', 'Please input msg.', 'warning');
                return;
            }
        }

        if (this.settings.getUserSetting('allowed_g2f') == 1) {
            this.g2f_code = '';
            this.showG2FModal = true;
        } else if (this.settings.getUserSetting('allowed_g2f') == 2) {
            this.coinApi.sendSMSCode({}).subscribe( res=> {
                if (res.success) {
                    this.sms_code = '';
                    this.showSMSModal = true;
                }
            });
        } else if (this.settings.getUserSetting('allowed_g2f') == 3) {
            this.settings.loading = true;
            this.coinApi.sendEmailCode({}).subscribe( res=> {
                this.settings.loading = false;
                if (res.success) {
                    this.email_code = '';
                    this.showEmailModal = true;
                }
            }, err=> {
                this.settings.loading = false;
                this.notify.showNotification('Error', 'We can not send email', 'warning');
            });
        } else {
            this.notify.showNotification('Warning', 'You can not withdraw.', 'warning');
            return;
        }
    }

    submitG2fCode() {
        if (this.g2f_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.settings.loading = true;
        this.coinApi.confirmG2FCode({
            code: this.g2f_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.submitWithdraw();
                this.showG2FModal = false;
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    submitSMSCode() {
        if (this.sms_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.settings.loading = true;
        this.coinApi.confirmSMSCode({
            code: this.sms_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.submitWithdraw();
                this.showSMSModal = false;
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    submitEmailCode() {
        if (this.email_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.settings.loading = true;
        this.coinApi.confirmEmailCode({
            code: this.email_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.submitWithdraw();
                this.showEmailModal = false;
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    cancelWithdraw(id) {
        if (id == null || id == 0) {
            return;
        }

        let _parent = this;
        swal({
            title: 'Are you sure?',
            text: 'Your withdraw transaction will be removed permanently.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'btn btn-success',
            cancelButtonClass: 'btn btn-danger',
            confirmButtonText: 'Yes',
            buttonsStyling: false
        }, function () {
            _parent.settings.loading = true;
            _parent.coinApi.cancelWithdraw(_parent.selCoin, {
                id: id,
            }).subscribe(res => {
                _parent.settings.loading = false;

                if (res.success) {
                    _parent.notify.showNotification('Success', 'Your withdrawal was canceled successfully.', 'success');
                    _parent.getWithdrawList();
                    _parent.balance.getCoinBalance(_parent.selCoin);
                } else {
                    _parent.notify.showNotification('Warning', res.error, 'warning');
                }
            }, err => {
                _parent.settings.loading = false;
            });
        });
    }
}