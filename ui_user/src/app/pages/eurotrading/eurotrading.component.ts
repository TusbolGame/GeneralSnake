import {Component, AfterViewInit, OnInit} from '@angular/core';
import {CoinApi} from "../../services/coinApi.service";
import {SettingsService} from "../../services/settings/settings.service";
import {Notifications} from "../../services/notifications.service";
import {BalanceService} from "../../services/balance.service";
import {Router} from "@angular/router";
import {TranslateService} from "@ngx-translate/core";
import {TranslatorService} from "../../services/translator.service";

declare var swal: any;

@Component({
    templateUrl: './eurotrading.component.html',
    styleUrls: ['./eurotrading.component.scss']
})
export class EUROTradingComponent implements OnInit, AfterViewInit {
    profile:any = {};
    iban_code_euro = '';

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

    showEPayDespositModal = false;
    showEPayWithdrawModal = false;
    isEpayWithdraw = false;

    constructor(public coinApi: CoinApi,
                public settings: SettingsService,
                public notify: Notifications,
                public balance: BalanceService,
                public router: Router,
                public translator: TranslatorService
    ) {

    }

    ngOnInit() {
        if (this.router.url.includes('epay') && this.router.url.includes('success')) {
            this.notify.showNotification('Success', 'Your ePay deposit was saved successfully.', 'success');
        } else if (this.router.url.includes('epay') && this.router.url.includes('fail')) {
            this.notify.showNotification('Warning', 'Unfortunately your ePay deposit was failed.', 'warning');
        }

        this.coinApi.getProfile({}).subscribe( res => {
            if (res.success) {
                this.profile = res.data;
                if (this.profile && this.profile.iban_code_euro) {
                    this.iban_code_euro = this.profile.iban_code_euro;

                    this.withdraw.to_address = this.iban_code_euro;
                    this.deposit.from_address = this.iban_code_euro;
                }
            }
        });

        this.coinApi.getBanks({
            currency: 'EURO'
        }).subscribe( res => {
            if (res.success) {
                this.banks = res.data;
            }
        });

        this.loadPendingTransactions();
    }

    ngAfterViewInit() {
    }

    withdrawEUROAll() {
        this.withdraw.amount = this.settings.getBalance('EURO');
    }

    loadPendingTransactions() {
        this.coinApi.getFinanceTransaction('EURO', {
            is_pending: true,
            limit_count: 20
        }).subscribe( res => {
            if (res.data) {
                this.pendingLists = res.data;
            }
        });
    }

    loadPastTransactions() {
        this.coinApi.getFinanceTransaction('EURO', {
            is_success: true,
            limit_count: 20
        }).subscribe( res => {
            if (res.data) {
                this.pastLists = res.data;
            }
        });
    }

    depositEURO() {
        this.deposit.fee = this.settings.getSystemSetting('euro_deposit_fee');

        this.settings.loading = true;
        this.coinApi.financeDeposit('EURO', this.deposit).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.loadPendingTransactions();
                this.notify.showNotification('Success', 'Your deposit was saved successfully. Please wait while confirming.', 'success');

                this.depositinfo = res.data;
                for (var i = 0; i < this.banks.length; i ++) {
                    if (this.banks[i].name == this.depositinfo.to_address) {
                        this.depositinfo.iban_code_euro = this.banks[i].iban_code;
                        break;
                    }
                }

                this.depositinfo.amount = this.deposit.amount;

                this.deposited = true;
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Please try again', 'error');
        });
    }

    sumbitWithdrawEURO() {
        this.showG2FModal = false;
        this.showSMSModal = false;
        this.showEmailModal = false;

        this.withdraw.isEpay = this.isEpayWithdraw;
        this.withdraw.fee = this.settings.getSystemSetting('euro_withdraw_fee');

        this.settings.loading = true;
        this.coinApi.financeWithdraw('EURO', this.withdraw).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.loadPendingTransactions();
                this.balance.getCoinBalance('EURO');

                this.notify.showNotification('Success', 'Your withdraw was saved successfully. Please wait while confirming.', 'success');
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Please try again', 'error');
        });

    }

    withdrawEURO() {
        let curLang = this.translator.getCurrentLangCode();

        if (curLang == 'tr') {
            this.notify.showNotification('Warning', 'EURO çekimleri kapalıdır, lütfen canlı destek ile iletişime geçiniz.', 'warning');
            return;
        } else {
            this.notify.showNotification('Warning', 'EURO withdrawal is suspended, please contact with live support.', 'warning');
            return;
        }

        // if (this.settings.getUserSetting('account_verified') == 0) {
        //     this.notify.showNotification('Warning', 'You have to verify your account.', 'warning');
        //     return;
        // }
        //
        // this.isEpayWithdraw = false;
        // if (this.settings.getUserSetting('allowed_g2f') == 1) {
        //     this.g2f_code = '';
        //     this.showG2FModal = true;
        // } else if (this.settings.getUserSetting('allowed_g2f') == 2) {
        //     this.coinApi.sendSMSCode({}).subscribe( res=> {
        //         if (res.success) {
        //             this.sms_code = '';
        //             this.showSMSModal = true;
        //         }
        //     });
        // }  else if (this.settings.getUserSetting('allowed_g2f') == 3) {
        //     this.settings.loading = true;
        //     this.coinApi.sendEmailCode({}).subscribe( res=> {
        //         this.settings.loading = false;
        //         if (res.success) {
        //             this.email_code = '';
        //             this.showEmailModal = true;
        //         }
        //     }, err => {
        //         this.settings.loading = false;
        //         this.notify.showNotification('Error', 'We can not send email', 'warning');
        //     });
        // } else {
        //     this.notify.showNotification('Warning', 'You can not withdraw.', 'warning');
        //     return;
        // }
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
                this.sumbitWithdrawEURO();
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
                this.sumbitWithdrawEURO();
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
                this.sumbitWithdrawEURO();
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
                this.loadPendingTransactions();
                this.balance.getCoinBalance('EURO');
                this.notify.showNotification('Success', 'Your withdraw was canceled successfully.', 'success');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Please try again', 'error');
        });
    }


    onClickDepositEpay() {
        this.notify.showNotification('Warning', 'Epay Deposit is closed temporarily. Please try again later', 'error');
        //this.showEPayDespositModal = true;
    }

    onClickWithdrawEpay() {
        let curLang = this.translator.getCurrentLangCode();

        if (curLang == 'tr') {
            this.notify.showNotification('Warning', 'EURO çekimleri kapalıdır, lütfen canlı destek ile iletişime geçiniz.', 'warning');
            return;
        } else {
            this.notify.showNotification('Warning', 'EURO withdrawal is suspended, please contact with live support.', 'warning');
            return;
        }
        // this.showEPayWithdrawModal = true;
    }

    submitEpayDeposit() {
        if (this.deposit.amount == null || this.deposit.amount < 100) {
            this.notify.showNotification('Warning', 'You have to input amount greater than 100.', 'warning');
            return;
        }

        this.settings.loading = true;
        this.coinApi.getEpayReceiveInfo({
            amount: this.deposit.amount,
            currency: 'EUR'
        }).subscribe( res => {
            this.settings.loading = false;
            if (res.success) {
                $('#PAYMENT_ID').val(res.paymentID);
                $('#V2_HASH').val(res.v2Hash);

                $('#form').submit();
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'Error', 'error');
        });
    }

    submitEpayWithdraw() {
        if (this.settings.getUserSetting('account_verified') == 0) {
            this.notify.showNotification('Warning', 'You have to verify your account.', 'warning');
            return;
        }

        if (this.withdraw.amount == null || this.withdraw.amount < 100) {
            this.notify.showNotification('Warning', 'You have to input amount greater than 100.', 'warning');
            return;
        }

        if (this.withdraw.epay_account == null || this.withdraw.epay_account == '') {
            this.notify.showNotification('Warning', 'You have to input your account.', 'warning');
            return;
        }

        this.showEPayWithdrawModal = false;
        this.isEpayWithdraw = true;
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
        }  else if (this.settings.getUserSetting('allowed_g2f') == 3) {
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
}