import {Component, AfterViewInit, OnInit} from '@angular/core';
import {CoinApi} from "../../services/coinApi.service";
import {SettingsService} from "../../services/settings/settings.service";
import {Notifications} from "../../services/notifications.service";
import {BalanceService} from "../../services/balance.service";
import {TranslatorService} from "../../services/translator.service";

declare var swal: any;

@Component({
    templateUrl: './tltrading.component.html',
    styleUrls: ['./tltrading.component.scss']
})
export class TLTradingComponent implements OnInit, AfterViewInit {
    profile: any = {};
    iban_code = '';

    pendingLists: any = [];
    pastLists: any = [];

    withdraw:any = {
        amount: 0
    };
    deposit:any = {
        amount: 0
    };

    banks: any = [];

    showG2FModal = false;
    g2f_code = '';

    showSMSModal = false;
    sms_code = '';

    showEmailModal = false;
    email_code = '';

    depositinfo: any = {};
    deposited = false;

    constructor(public coinApi: CoinApi,
                public settings: SettingsService,
                public notify: Notifications,
                public balance: BalanceService,
                public translator: TranslatorService
    ) {

    }

    ngOnInit() {
        this.coinApi.getProfile({}).subscribe( res => {
            if (res.success) {
                this.profile = res.data;
                if (this.profile && this.profile.iban_code) {
                    this.iban_code = this.profile.iban_code;

                    this.withdraw.to_address = this.iban_code;
                    this.deposit.from_address = this.iban_code;
                }
            }
        });

        this.coinApi.getBanks({
            currency: 'TL'
        }).subscribe( res => {
            if (res.success) {
                this.banks = res.data;
            }
        });

        this.loadPendingTransactions();
    }

    ngAfterViewInit() {
    }

    withdrawTLAll() {
        this.withdraw.amount = this.settings.getBalance('TL');
    }

    loadPendingTransactions() {
        this.coinApi.getFinanceTransaction('TL', {
            is_pending: true,
            limit_count: 20
        }).subscribe( res => {
           if (res.data) {
               this.pendingLists = res.data;
           }
        });
    }

    loadPastTransactions() {
        this.coinApi.getFinanceTransaction('TL', {
            is_success: true,
            limit_count: 20
        }).subscribe( res => {
            if (res.data) {
                this.pastLists = res.data;
            }
        });
    }

    depositTL() {
        let _parent = this;
        let headerText = 'UYARI';
        let warningText = 'Banka hesap bilgilerimiz değişmiştir. Lütfen belirtilen Hesap Adı ve Hesap Numarasına gönderim yapınız. Aksi takdirde yatırım tutarınız hesabınıza yansımayacaktır.';
        let currentLang:any = this.translator.getCurrentLang();
        if (currentLang.code != 'tr') {
            headerText = 'WARNING';
            warningText = 'Our bank account information has changed. Please send your funds to new account name and number. Otherwise, your investment amount will not be reflected in your account.';
        }
        swal({
            title: headerText,
            text: warningText,
            type: 'warning',
            confirmButtonClass: 'btn btn-success',
            confirmButtonText: 'OK',
            buttonsStyling: false
        }, function () {
            _parent.deposit.fee = _parent.settings.getSystemSetting('tl_deposit_fee');

            _parent.settings.loading = true;
            _parent.coinApi.financeDeposit('TL', _parent.deposit).subscribe( res=> {
                _parent.settings.loading = false;
                if (res.success) {
                    _parent.loadPendingTransactions();
                    _parent.notify.showNotification('Success', 'Your deposit was saved successfully. Please wait while confirming.', 'success');

                    _parent.depositinfo = res.data;
                    for (var i = 0; i < _parent.banks.length; i ++) {
                        if (_parent.banks[i].name == _parent.depositinfo.to_address) {
                            _parent.depositinfo.iban_code = _parent.banks[i].iban_code;
                            break;
                        }
                    }

                    _parent.depositinfo.amount = _parent.deposit.amount;

                    _parent.deposited = true;
                } else {
                    _parent.notify.showNotification('Warning', res.error, 'warning');
                }
            }, err => {
                _parent.settings.loading = false;
                _parent.notify.showNotification('Error', 'Please try again', 'error');
            });
        });
    }

    sumbitWithdrawTL() {
        this.showG2FModal = false;
        this.showSMSModal = false;

        this.withdraw.fee = this.settings.getSystemSetting('tl_withdraw_fee');

        this.settings.loading = true;
        this.coinApi.financeWithdraw('TL', this.withdraw).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.loadPendingTransactions();
                this.balance.getCoinBalance('TL');

                this.notify.showNotification('Success', 'Your withdraw was saved successfully. Please wait while confirming.', 'success');
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Please try again', 'error');
        });

    }

    withdrawTL() {
        if (this.settings.getUserSetting('account_verified') == 0) {
            this.notify.showNotification('Warning', 'You have to verify your account.', 'warning');
            return;
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
            }, err => {
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
                this.sumbitWithdrawTL();
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
                this.sumbitWithdrawTL();
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
                this.sumbitWithdrawTL();
                this.showEmailModal = false;
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    cancelTransaction(id) {
        this.settings.loading = true;
        this.coinApi.cancelFinanceTransaction({
            id: id
        }).subscribe( res => {
            this.settings.loading = false;
            if (res.success) {
                this.balance.getCoinBalance('TL');
                this.loadPendingTransactions();
                this.notify.showNotification('Success', 'Your withdraw was canceled successfully.', 'success');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Please try again', 'error');
        });
    }
}