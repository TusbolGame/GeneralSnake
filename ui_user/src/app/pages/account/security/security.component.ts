import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {Api} from "../../../services/api.service";
import {SettingsService} from "../../../services/settings/settings.service";
import {BalanceService} from "../../../services/balance.service";
import {Notifications} from "../../../services/notifications.service";
import {PusherService} from "../../../services/pusher.service";
import {Subject} from "rxjs";
import {takeUntil} from "rxjs/operators";
import {SystemService} from "../../../services/system.service";

declare const $: any;
declare const swal: any;
@Component({
    templateUrl: './security.component.html',
    styleUrls: ['./security.component.scss']
})
export class SecurityComponent implements OnInit, AfterViewInit, OnDestroy {

    profile: any = {};

    showG2FModal = false;
    showSMSModal = false;
    showEmailModal = false;

    showG2FEnableModal = false;
    showSMSEnableModal = false;
    showEmailEnableModal = false;

    g2f_mode = 1;

    g2f_security_code = '';
    g2f_code = '';
    sms_code = '';
    email_code = '';

    phone_number = '';

    constructor(public api: Api,
                public settings: SettingsService,
                public balance: BalanceService,
                public notify: Notifications,
                public pusher: PusherService,
                public system: SystemService
    ) {


    }


    ngOnInit() {
        this.loadProfile();
    }

    loadProfile() {
        this.api.getProfile({}).subscribe(res => {
            if (res.success) {
                this.profile = res.data;
                this.g2f_mode = res.data.allowed_g2f;
            }
        });
    }

    onSelectMode() {
        if (this.g2f_mode == this.profile.allowed_g2f) {
            return;
        }

        if (this.profile.allowed_g2f == 1) {
            this.showG2FModal = true;
        } else if (this.profile.allowed_g2f == 2) {
            this.api.sendSMSCode({}).subscribe( res=> {
                if (res.success) {
                    this.sms_code = '';
                    this.showSMSModal = true;
                }
            });
        } else if (this.profile.allowed_g2f == 3) {
            this.settings.loading = true;
            this.api.sendEmailCode({}).subscribe( res=> {
                this.settings.loading = false;
                if (res.success) {
                    this.email_code = '';
                    this.showEmailModal = true;
                }
            }, err=> {
                this.settings.loading = false;
                this.notify.showNotification('Warning', 'Please try again later', 'warning');
            });
        }
    }

    onEnableMode() {
        if (this.g2f_mode == 1) {

            this.g2f_code = '';
            this.g2f_security_code = '';

            this.api.generateNewG2FSecurityCode({
            }).subscribe( res => {
                if (res.success) {
                    this.g2f_security_code = res.code;
                    this.showG2FEnableModal = true;
                }
            });
        } else if (this.g2f_mode == 2) {
            let phone_number = this.profile.phone_number;

            let smsTimer = setInterval(function() {
                if ($('#sms_phone').length > 0 && typeof $("#sms_phone").intlTelInput("getNumber") !== 'string') {

                    $("#sms_phone").intlTelInput({
                        placeholderNumberType: "MOBILE",
                        preferredCountries: ['tr'],
                        separateDialCode: true,
                        utilsScript: 'assets/js/utils.js'
                    });

                    $("#sms_phone").intlTelInput("setNumber", phone_number);
                    $('.intl-tel-input').css('width', '100%');

                    clearInterval(smsTimer);
                }
            }, 200);

            this.sms_code = '';
            this.showSMSEnableModal = true;
        } else if (this.g2f_mode == 3) {
            this.email_code = '';
            this.showEmailEnableModal = true;
        }
    }
    submitG2fCode() {
        if (this.g2f_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.settings.loading = true;
        this.api.confirmG2FCode({
            code: this.g2f_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.showG2FModal = false;

                this.onEnableMode();
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
        this.api.confirmSMSCode({
            code: this.sms_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.showSMSModal = false;

                this.onEnableMode();
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    sendSMS() {
        this.settings.loading = true;
        this.api.sendUserSMSCode({
            email: this.settings.getUserSetting('email'),
            phone_number: $("#sms_phone").intlTelInput("getNumber")
        }).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Success', 'Please check sms code on your phone.', 'success');
            } else {
                this.notify.showNotification('Warning', 'Your phone number is wrong.', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    enableSMS() {
        if (this.sms_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.settings.loading = true;
        this.api.confirmSMSCode({
            code: this.sms_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.showSMSEnableModal = false;

                this.api.saveProfile({
                    id: this.profile.id,
                    allowed_g2f: 2
                }).subscribe(res => {
                    if (res.success) {
                        this.notify.showNotification('Success', 'Your 2F Mode was updated successfully.', 'success');
                        this.loadProfile();
                        this.settings.setUserSetting('allowed_g2f', 2);
                    } else {
                        this.notify.showNotification('Warning', 'Please try again.', 'warning');
                    }
                });
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    enableG2F() {
        this.settings.loading = true;
        this.api.confirmG2FCode({
            code: this.g2f_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.showG2FEnableModal = false;

                this.api.saveProfile({
                    id: this.profile.id,
                    allowed_g2f: 1
                }).subscribe(res => {
                    if (res.success) {
                        this.notify.showNotification('Success', 'Your 2F Mode was updated successfully.', 'success');
                        this.loadProfile();
                        this.settings.setUserSetting('allowed_g2f', 1);
                    } else {
                        this.notify.showNotification('Warning', 'Please try again.', 'warning');
                    }
                });
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    sendEmail() {
        this.settings.loading = true;
        this.api.sendEmailCode({}).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.notify.showNotification('Success', 'Please check code on your email.', 'success');
            } else {
                this.notify.showNotification('Warning', 'Your email is wrong.', 'warning');
            }
        }, err => {
            this.settings.loading = false;
            this.notify.showNotification('Warning', 'Your email is wrong.', 'warning');
        });
    }

    enableEmail() {
        if (this.email_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.settings.loading = true;
        this.api.confirmEmailCode({
            code: this.email_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.showEmailEnableModal = false;

                this.api.saveProfile({
                    id: this.profile.id,
                    allowed_g2f: 3
                }).subscribe(res => {
                    if (res.success) {
                        this.notify.showNotification('Success', 'Your 2F Mode was updated successfully.', 'success');
                        this.loadProfile();
                        this.settings.setUserSetting('allowed_g2f', 3);
                    } else {
                        this.notify.showNotification('Warning', 'Please try again.', 'warning');
                    }
                });
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
        this.api.confirmEmailCode({
            code: this.email_code
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.showEmailModal = false;

                this.onEnableMode();
            } else {
                this.notify.showNotification('Warning', 'Wrong Code! Please enter correct code', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    ngOnDestroy() {
    }

    ngAfterViewInit() {
    }

}