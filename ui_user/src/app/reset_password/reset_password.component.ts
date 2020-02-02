import {Component, OnInit, ElementRef, ViewChild, AfterViewInit} from '@angular/core';
import {FormBuilder, FormGroup, Validators, FormControl, AbstractControl} from '@angular/forms';
import {Notifications} from "../services/notifications.service";
import {Api} from "../services/api.service";
import {ActivatedRoute, ActivatedRouteSnapshot, Params, Router} from "@angular/router";
import {SettingsService} from "../services/settings/settings.service";
import {BalanceService} from "../services/balance.service";
import {Validate} from "../services/validate.service";
import {PasswordValidation} from "../shared/components/password-validator.component";

declare var $: any;

@Component({
    selector: 'app-resetpassword-cmp',
    templateUrl: './reset_password.component.html',
    styleUrls: ['./reset_password.component.scss']
})

export class ResetPasswordComponent implements OnInit, AfterViewInit {
    reset: FormGroup;
    user: any = {};
    sub: any;
    confirm_code: any;

    constructor(private element: ElementRef,
                private formBuilder: FormBuilder,
                public api: Api,
                public notify: Notifications,
                public router: Router,
                public settings: SettingsService,
                public balance: BalanceService,
                public validate: Validate,
                private activatedRoute: ActivatedRoute,
    ) {
        // console.log(activatedRoute.params._value['code']);
        this.confirm_code = this.activatedRoute.snapshot.params['code'];
    }

    ngOnInit() {
        // this.sub = this.activatedRoute.data
        //     .subscribe(data => {
        //         this.user.id = data.user_id;
        //     });

        this.user.confirm_code = this.confirm_code;

        this.reset = this.formBuilder.group({
            password: ['', Validators.required],
            confirmPassword: ['', Validators.required],
        }, {
            validator: PasswordValidation.MatchPassword // your validation method
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

    onResetPassword() {
        if (this.reset.valid) {
            this.api.resetPassword(this.user).subscribe(res => {
                if (res.success) {
                    this.notify.showNotification('Success', 'Password was changed succesfully', 'success');
                    this.router.navigate(['']);
                } else {
                    this.notify.showNotification('Warning', res.error, 'warning');
                }
            }, err => {
                this.notify.showNotification('Error', 'Failed', 'error');
            });
        } else {
            this.validate.validateAllFormFields(this.reset);
        }
    }
}
