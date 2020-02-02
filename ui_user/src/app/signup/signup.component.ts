import {Component, OnInit, AfterViewInit} from '@angular/core';
import {Router} from '@angular/router';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {Validate} from "../services/validate.service";
import {PasswordValidation} from "../shared/components/password-validator.component";
import {Api} from "../services/api.service";
import {Notifications} from "../services/notifications.service";
import {SettingsService} from "../services/settings/settings.service";

@Component({
    selector: 'app-signup',
    templateUrl: './signup.component.html',
    styleUrls: ['./signup.component.scss']
})
export class SignupComponent implements OnInit, AfterViewInit {
    signup: FormGroup;
    user: any = {
        agree: true
    };

    constructor(public router: Router,
                private formBuilder: FormBuilder,
                public validate: Validate,
                public api: Api,
                public notify: Notifications,
                public settings: SettingsService
    ) {
    }

    ngOnInit() {
        this.signup = this.formBuilder.group({
            name: [null, Validators.required],
            email: [null, [Validators.required, Validators.pattern("^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$")]],
            password: ['', Validators.required],
            confirmPassword: ['', Validators.required],
            agree: ['']
        }, {
            validator: PasswordValidation.MatchPassword // your validation method
        });
    }

    ngAfterViewInit() {
        $(function () {
            $(".preloader").fadeOut();
        });
        $(function () {
            (<any>$('[data-toggle="tooltip"]')).tooltip()
        });
        $('#to-recover').on("click", function () {
            $("#loginform").slideUp();
            $("#recoverform").fadeIn();
        });
    }

    register() {
        if (this.signup.valid) {
            if (!this.user.agree) {
                alert('Please check');
                return;
            }

            this.settings.loading = true;
            this.api.register(this.user).subscribe(res => {
                this.settings.loading = false;
                if (res.success) {
                    this.notify.showNotification('Success', 'Registered Successfully', 'success');
                    this.router.navigate(['/login']);
                } else {
                    this.notify.showNotification('Eror', res.error, 'warning');
                }
            }, err => {
                this.settings.loading = false;
                this.notify.showNotification('Error', 'DB Error', 'error');
            });
        } else {
            this.validate.validateAllFormFields(this.signup);
        }
    }

}
