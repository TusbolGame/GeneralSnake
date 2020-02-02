import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {Api} from "../../../services/api.service";
import {SettingsService} from "../../../services/settings/settings.service";
import {BalanceService} from "../../../services/balance.service";
import {Notifications} from "../../../services/notifications.service";
import {PusherService} from "../../../services/pusher.service";
import {Subject} from "rxjs";
import {takeUntil} from "rxjs/operators";
import {SystemService} from "../../../services/system.service";

declare const swal: any;
@Component({
    templateUrl: './bank.component.html',
    styleUrls: ['./bank.component.scss']
})
export class BankComponent implements OnInit, AfterViewInit, OnDestroy {
    profile: any = {};

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
        this.api.getProfile({}).subscribe( res => {
            if (res.success) {
                this.profile = res.data;
            }
        });
    }

    ngOnDestroy() {
    }

    ngAfterViewInit() {
    }

    saveProfile() {
        this.api.saveProfile({
            id: this.profile.id,
            iban_code: this.profile.iban_code,
            iban_code_usd: this.profile.iban_code_usd,
            iban_code_euro: this.profile.iban_code_euro
        }).subscribe(res => {
            if (res.success) {
                this.notify.showNotification('Success', 'IBAN CODE was updated successfully.', 'success');
                this.loadProfile();
            } else {
                this.notify.showNotification('Warning', 'Please try again.', 'warning');
            }
        }, err => {
            this.notify.showNotification('Error', 'Please try again later', 'error');
        });
    }
}