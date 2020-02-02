import {Component, AfterViewInit, OnInit} from '@angular/core';
import {NgbModule} from '@ng-bootstrap/ng-bootstrap';
import {TranslateService} from "@ngx-translate/core";
import {SettingsService} from "../../services/settings/settings.service";
import {Router} from "@angular/router";
import {PusherService} from "../../services/pusher.service";
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {PasswordValidation} from "../components/password-validator.component";
import {Validate} from "../../services/validate.service";
import {Api} from "../../services/api.service";
import {Notifications} from "../../services/notifications.service";
import {TranslatorService} from "../../services/translator.service";

declare const $: any;

@Component({
    selector: 'ap-navigation',
    templateUrl: './navigation.component.html',
    styleUrls: ['./navigation.component.scss']
})
export class NavigationComponent implements OnInit, AfterViewInit {
    name: string;
    showHide: boolean;

    showPwdModal = false;

    changepwd: FormGroup;
    pwd: any = {};

    currentLang: any = {};
    constructor(
        public settings: SettingsService,
        public router: Router,
        public pusher: PusherService,
        private formBuilder: FormBuilder,
        public validate: Validate,
        public api: Api,
        public notify: Notifications,
        public translator: TranslatorService,
        public translate: TranslateService
    ) {
        this.showHide = true;
    }

    changeShowStatus() {
        this.showHide = !this.showHide;
    }

    ngOnInit() {
        this.currentLang = this.translator.getCurrentLang();

        this.changepwd = this.formBuilder.group({
            curPassword: ['', Validators.required],
            password: ['', Validators.required],
            confirmPassword: ['', Validators.required],
        }, {
            validator: PasswordValidation.MatchPassword // your validation method
        });
    }

    isMobile() {
        if ($(window).width() < 991) {
            return true;
        }
        return false;
    }
    ngAfterViewInit() {

        $(function () {
            $(".preloader").fadeOut();
        });

        var set = function () {
            var width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
            var topOffset = 70;
            if (width < 1170) {
                $("body").addClass("mini-sidebar");
                $('.navbar-brand span').hide();
                $(".scroll-sidebar, .slimScrollDiv").css("overflow-x", "visible").parent().css("overflow", "visible");
                $(".sidebartoggler i").addClass("ti-menu");
            } else {
                $("body").removeClass("mini-sidebar");
                $('.navbar-brand span').show();
                //$(".sidebartoggler i").removeClass("ti-menu");
            }

            var height = ((window.innerHeight > 0) ? window.innerHeight : this.screen.height) - 1;
            height = height;
            // height = height - topOffset;
            if (height < 1) height = 1;
            if (height > topOffset) {
                $(".page-wrapper").css("min-height", (height) + "px");
            }

        };
        $(window).ready(set);
        $(window).on("resize", set);

        $(document).on('click', '.mega-dropdown', function (e) {
            e.stopPropagation();
        });

        $(".search-box a, .search-box .app-search .srh-btn").on('click', function () {
            $(".app-search").toggle(200);
        });

        (<any>$('[data-toggle="tooltip"]')).tooltip();

        // (<any>$('.scroll-sidebar')).slimScroll({
        //     position: 'left',
        //     size: "5px",
        //     height: '100%',
        //     color: '#dcdcdc'
        // });

        (<any>$('.right-sidebar')).slimScroll({
            height: '100%',
            position: 'right',
            size: "5px",
            color: '#dcdcdc'
        });

        (<any>$('.message-center')).slimScroll({
            position: 'right',
            size: "5px",
            color: '#dcdcdc'
        });

        $("body").trigger("resize");
    }

    logout() {
        this.api.logout({}).subscribe(res => {

        });
        this.settings.setAppSetting('is_loggedin', false);
        this.settings.user = {};
        this.settings.setStorage('token', false);

        this.pusher.disconnect();

        this.router.navigate(['/login']);
    }

    onChangePwd() {
        this.pwd = {};
        this.showPwdModal = true;
    }
    changePwd() {

        if (this.changepwd.valid) {
            this.api.changePassword(this.pwd).subscribe(res => {
                if (res.success) {
                    this.notify.showNotification('Success', 'Your password changed successfully.', 'success');
                    this.showPwdModal = false;
                } else {
                    this.notify.showNotification('Warning', res.error, 'warning');
                }
            }, err => {
                this.notify.showNotification('Error', 'Failed', 'error');
            });
        } else {
            this.validate.validateAllFormFields(this.changepwd);
        }
    }

    setLang(code) {
        this.translator.useLanguage(code);

        this.currentLang = this.translator.getCurrentLang();
    }
}
