import {Component, OnInit, OnDestroy, ViewChild, HostListener, AfterViewInit} from '@angular/core';
import {Router, NavigationEnd} from '@angular/router';
import {Subscription} from 'rxjs/Subscription';
import {LocationStrategy, PlatformLocation, Location} from '@angular/common';
import 'rxjs/add/operator/filter';
import {SettingsService} from "../../services/settings/settings.service";

declare const $: any;

@Component({
    selector: 'app-auth-layout',
    templateUrl: './auth-layout.component.html',
    styleUrls: ['./auth-layout.component.scss']
})

export class AuthLayoutComponent implements OnInit, AfterViewInit {
    constructor(
        public router: Router,
        public settings: SettingsService
    ) {
    }

    ngOnInit() {
        if (this.router.url === '/') {
            this.router.navigate(['/market']);
        }

        if (this.settings.getUserSetting('role') == 'admin') {
            console.log('admin');
            this.router.navigate(['/admin/dashboard']);
        }
    }

    ngAfterViewInit() {

    }
}
