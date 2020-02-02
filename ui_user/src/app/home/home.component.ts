import {Component, OnInit, AfterViewInit, OnDestroy, AfterContentInit} from '@angular/core';
import {ActivatedRoute, ActivatedRouteSnapshot, Router} from '@angular/router';
import {Api} from "../services/api.service";
import {SettingsService} from "../services/settings/settings.service";
import {Notifications} from "../services/notifications.service";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {Validate} from "../services/validate.service";
import {BalanceService} from "../services/balance.service";
import {PusherService} from "../services/pusher.service";
import {SystemService} from "../services/system.service";
import {TranslatorService} from "../services/translator.service";
import {PasswordValidation} from "../shared/components/password-validator.component";
import {MarketApi} from "../services/marketApi.service";
import {Base58Service} from "../services/base58.service";

declare const $: any;
declare const window: any;
declare var swal: any;

@Component({
    selector: 'app-home',
    templateUrl: './home.component.html',
    styleUrls: ['./home.component.scss']
})
export class HomeComponent implements OnInit, AfterViewInit, OnDestroy {
    signup: FormGroup;
    login: FormGroup;

    user: any = {};
    loginInfo: any = {};

    signupUser: any = {
        agree: false
    };

    currentLang: any = {
        code: 'tr'
    };

    res: any = {};
    mail_type = '';
    showMailModal = false;
    confirm_email = '';

    register: boolean = true;

    showG2FModal = false;
    g2f_code = '';

    showSMSModal = false;
    sms_code = '';

    showEmailModal = false;
    email_code = '';

    showSelectModeModal = false;
    selectedMode = 'g2f';

    prices: any = [];
    before_prices: any = [];

    timerID: any;
    timerID1: any;
    timerID2: any;

    showG2FEnableModal = false;
    g2f_security_code = '';

    showSMSEnableModal = false;
    phone_number = '';

    constructor(public router: Router,
                private formBuilder: FormBuilder,
                public api: Api,
                public marketApi: MarketApi,
                public settings: SettingsService,
                public validate: Validate,
                public notify: Notifications,
                public balance: BalanceService,
                public pusherService: PusherService,
                public system: SystemService,
                public base58: Base58Service,
                public translator: TranslatorService) {
        // if (this.router.url.includes('/confirmed')) {
        //     this.confirmed_user_email = activatedRouteSnapshot.params['user_id'];
        // }
    }

    ngOnInit() {
        console.log(this.base58.decode('TNgecdBdYY7ZvMC1p2ycPiyJ1siwHcS6KQ'));
        if (this.settings.getAppSetting('is_loggedin')) {
            this.router.navigate(['/market']);
        }

        this.login = this.formBuilder.group({
            email: [null, [Validators.required, Validators.pattern("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,4}$")]],
            password: ['', Validators.required]
        });

        this.signup = this.formBuilder.group({
            name: [null, Validators.required],
            surname: [null, Validators.required],
            phone: [null, Validators.required],
            email: [null, [Validators.required, Validators.pattern("^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-z]{2,4}$")]],
            password: ['', Validators.required],
            confirmPassword: ['', Validators.required],
            agree: [''],
            ref_name: ['']
        }, {
            validator: PasswordValidation.MatchPassword // your validation method
        });

        if (this.router.url.includes('/confirmed')) {
            this.notify.showNotification('Success', 'Your account was confirmed successfully. You have to set Two Factor Authenticator.', 'success');
            let url = this.router.url;
            // this.onEnableG2F();

            this.register = false;
        }

        if (this.router.url.includes('/login')) {
            this.register = false;
        }

        if (this.router.url.includes('/ref')) {
            let url = this.router.url;
            let ref_code = url.substring(url.lastIndexOf('/') + 1);

            this.api.getUserByRefCode({
                ref_code: ref_code
            }).subscribe(res => {
                if (res.success) {
                    this.signupUser.ref_code = ref_code;
                    this.signupUser.ref_name = res.data.name;
                }
            });
        }

        // this.api.getHelpPage({}).subscribe( res=> {
        //     if (res.success) {
        //         this.allcontents = res.data;
        //     }
        // });

        this.currentLang = this.translator.getCurrentLang();

        this.loadPrices();

        var _parent = this;
        this.timerID = setInterval(function () {
            _parent.loadPrices();
        }, 5000);

        this.timerID1 = setInterval(function () {
            if ($('#phone').length > 0 && typeof $("#phone").intlTelInput("getNumber") !== 'string') {
                _parent.setIntlTelInput();
            }
        }, 200);

        $("#slideshow > div:gt(0)").hide();

        this.timerID2 = setInterval(function() {
            $('#slideshow > div:first')
                .fadeOut(1000)
                .next()
                .fadeIn(1000)
                .end()
                .appendTo('#slideshow');
        },  3000);
    }

    ngOnDestroy() {
        if (this.timerID) {
            clearInterval(this.timerID);
        }
        if (this.timerID1) {
            clearInterval(this.timerID1);
        }
        if (this.timerID2) {
            clearInterval(this.timerID2);
        }
    }

    loadPrices() {
        this.before_prices = this.prices.map(x => Object.assign({}, x));

        // this.marketApi.getCoinPairInfo({}).subscribe(res => {
        //     if (res.success) {
        //         this.prices = res.data.filter(pair => pair.market_coin === 'TL');
        //         if (this.before_prices.length == 0) {
        //             this.before_prices = this.prices.map(x => Object.assign({}, x));
        //         }
        //     }
        // });
    }

    setIntlTelInput() {
        $("#phone").intlTelInput({
            placeholderNumberType: "MOBILE",
            preferredCountries: ['tr'],
            separateDialCode: true,
            utilsScript: 'assets/js/utils.js'
        });

        $('.intl-tel-input').width('100%');
    }

    ngAfterViewInit() {
        // $('#video').on('loadeddata', function() {
        //
        //     $(function () {
        //         $(".preloader").fadeOut();
        //     });
        //     $(function () {
        //         (<any>$('[data-toggle="tooltip"]')).tooltip()
        //     });
        // });

        $(function () {
            $(".preloader").fadeOut();
        });
        $(function () {
            (<any>$('[data-toggle="tooltip"]')).tooltip()
        });

        this.setIntlTelInput();
    }

    successLogin(res) {
        this.settings.user = res;
        this.settings.setAppSetting('is_loggedin', true);
        this.settings.setStorage('token', res.token);

        if (res.role == 'admin' || res.role == 'superadmin') {
            this.router.navigate(['/admin/dashboard']);
        } else {
            this.system.init();
            this.router.navigate(['/market']);
        }

        this.pusherService.connect();
    }

    onLogin() {
        if (this.login.valid) {
            this.settings.loading = true;
            this.api.confirmPassword(this.loginInfo).subscribe(res => {
                this.settings.loading = false;

                if (res.success) {
                    this.user.email = this.loginInfo.email;
                    this.user.password = this.loginInfo.password;
                    this.user.allowed_g2f = res.allowed_g2f;

                    if (res.allowed_g2f == 1) {
                        this.g2f_code = '';
                        this.showG2FModal = true;
                    } else if (res.allowed_g2f == 2) {
                        this.sms_code = '';
                        this.showSMSModal = true;
                    } else if (res.allowed_g2f == 3) {
                        this.email_code = '';
                        this.showEmailModal = true;
                    } else {
                        this.notify.showNotification('Warning', 'You have to set two-factor authenticator.', 'warning');

                        this.onEnableTwoFactor();
                    }
                } else {
                    this.notify.showNotification('Error', res.error, 'warning');
                }
            }, err => {
                this.settings.loading = false;
                this.notify.showNotification('Error', err._body, 'error');
            });
        } else {
            this.validate.validateAllFormFields(this.login);
        }
    }


    forgot() {
        this.mail_type = 'forgot';
        this.showMailModal = true;
    }

    resend() {
        this.mail_type = 'resend';
        this.showMailModal = true;
    }

    submitEmail() {
        if (this.confirm_email == '') {
            this.notify.showNotification('Warning', 'Please enter email address', 'warning');
            return;
        }

        if (this.mail_type == 'forgot') {
            this.settings.loading = true;
            this.api.sendForgotEmail({
                email: this.confirm_email
            }).subscribe(res => {
                this.settings.loading = false;
                if (res.success) {
                    this.showMailModal = false;
                    this.notify.showNotification('Success', 'Email was sent successfully. Please check your email.', 'success');
                } else {
                    this.notify.showNotification('Warning', res.error, 'success');
                }
            }, er => {
                this.settings.loading = false;
                this.notify.showNotification('Error', 'Failed', 'error');
            });
        } else if (this.mail_type == 'resend') {
            this.settings.loading = true;

            this.api.sendActivateEmail({
                email: this.confirm_email
            }).subscribe(res => {
                this.settings.loading = false;

                if (res.success) {
                    this.showMailModal = false;
                    this.notify.showNotification('Success', 'Email was sent successfully. Please check your email.', 'success');
                } else {
                    this.notify.showNotification('Warning', res.error, 'success');
                }
            }, err => {
                this.settings.loading = false;
                this.notify.showNotification('Error', 'Failed', 'error');
            });
        }
    }

    onSignup() {
        if (this.signup.valid) {
            if (!this.signupUser.agree) {
                this.notify.showNotification('Warning', 'Lütfen kullanıcı sözleşmesini onaylayınız', 'warning');
                return;
            }
            var intlNumber = $("#phone").intlTelInput("getNumber");

            this.signupUser.phone_number = intlNumber;

            this.settings.loading = true;
            this.api.register(this.signupUser).subscribe(res => {
                this.settings.loading = false;
                if (res.success) {
                    this.notify.showNotification('Success', 'Registered Successfully', 'success');
                    this.register = false;
                } else {
                    this.notify.showNotification('Error', res.error, 'warning');
                }
            }, err => {
                this.settings.loading = false;
                this.notify.showNotification('Error', 'DB Error', 'error');
            });
        } else {
            this.validate.validateAllFormFields(this.signup);
        }
    }

    setLang(code) {
        this.translator.useLanguage(code);

        this.currentLang = this.translator.getCurrentLang();
    }

    onSelectMode() {
        if (this.selectedMode == 'g2f') {
            this.onEnableG2F();
        } else if (this.selectedMode == 'sms') {
            this.showSMSEnableModal = true;

            let phone_number = this.res.phone_number;

            let smsTimer = setInterval(function () {
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
        } else if (this.selectedMode == 'email') {
            this.settings.loading = true;
            this.api.sendUserEmailCode({
                email: this.user.email,
            }).subscribe(res => {
                this.settings.loading = false;
                if (res.success) {
                    this.user.allowed_g2f = 3;
                    this.showEmailModal = true;
                } else {
                    this.notify.showNotification('Warning', 'Please try again later.', 'warning');
                }
            }, err => {
                this.settings.loading = false;
            });
        }
        this.showSelectModeModal = false;
    }

    confirmCode() {
        this.settings.loading = true;
        this.api.confirmCodeAndLogin(this.user).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.successLogin(res);
                // let _parent = this;
                // swal({
                //     title: 'UYARI',
                //     text: 'Banka hesap bilgilerimiz değişmiştir. Lütfen belirtilen Hesap Adı ve Hesap Numarasına gönderim yapınız. Aksi takdirde yatırım tutarınız hesabınıza yansımayacaktır.',
                //     type: 'warning',
                //     confirmButtonClass: 'btn btn-success',
                //     confirmButtonText: 'OK',
                //     buttonsStyling: false
                // }, function () {
                // });
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    submitG2fCode() {
        if (this.g2f_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.user.code = this.g2f_code;

        this.confirmCode();
    }

    submitSMSCode() {
        if (this.sms_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.user.code = this.sms_code;

        this.confirmCode();
    }

    submitEmailCode() {
        if (this.email_code == '') {
            this.notify.showNotification('Warning', 'Please enter code', 'warning');
            return;
        }

        this.user.code = this.email_code;

        this.confirmCode();
    }

    onEnableTwoFactor() {
        this.showSelectModeModal = true;
    }

    onEnableG2F() {
        this.g2f_code = '';
        this.g2f_security_code = '';

        this.api.generateUserG2FSecurityCode({
            email: this.user.email
        }).subscribe(res => {
            if (res.success) {
                this.g2f_security_code = res.code;
                this.showG2FEnableModal = true;
            }
        });
    }

    enableG2F() {
        this.user.allowed_g2f = 1;

        this.showG2FEnableModal = false;
        this.showG2FModal = true;
    }

    sendSMS() {
        this.settings.loading = true;
        this.api.sendUserSMSCode({
            email: this.user.email,
            phone_number: $("#sms_phone").intlTelInput("getNumber")
        }).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.user.allowed_g2f = 2;
                this.showSMSEnableModal = false;
                this.showSMSModal = true;
            } else {
                this.notify.showNotification('Warning', 'Your phone number is wrong.', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }

    // onClickPage(name) {
    //     window.scrollY = 0;
    //     this.content_view = true;
    //
    //     this.contents = [];
    //
    //     this.selectedPage = name;
    //
    //     for (var i=0; i<this.allcontents.length; i++) {
    //         if (this.allcontents[i].name == name) {
    //             this.contents.push(this.allcontents[i]);
    //         }
    //     }
    // }
}
