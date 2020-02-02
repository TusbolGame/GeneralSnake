import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {Api} from "../../../services/api.service";
import {SettingsService} from "../../../services/settings/settings.service";
import {BalanceService} from "../../../services/balance.service";
import {Notifications} from "../../../services/notifications.service";
import {PusherService} from "../../../services/pusher.service";
import {Subject} from "rxjs";
import {takeUntil} from "rxjs/operators";
import {SystemService} from "../../../services/system.service";
import {CoinApi} from "../../../services/coinApi.service";

declare const swal: any;
@Component({
    templateUrl: './referral.component.html',
    styleUrls: ['./referral.component.scss']
})
export class ReferralComponent implements OnInit, AfterViewInit, OnDestroy {
    referral_url = '';
    qrcode_url = '';
    friends: any = [];
    commissions: any = {};
    history: any = [];

    coins: any = [];
    constructor(public api: Api,
                public coinApi: CoinApi,
                public settings: SettingsService,
                public balance: BalanceService,
                public notify: Notifications,
                public pusher: PusherService,
                public system: SystemService
    ) {


    }

    ngOnInit() {
        this.referral_url = this.settings.siteUrl + '/#/ref/' + this.settings.getUserSetting('ref_code');
        this.qrcode_url = this.settings.siteUrl + '/ref/' + this.settings.getUserSetting('ref_code');

        this.coinApi.getCoin({
        }).subscribe(res => {
            if (res.success) {
                this.coins = res.data;
            }
        }, err => {

        });

        this.api.getReferral({}).subscribe(res => {
            if (res.success) {
                this.friends = res.data;
            }
        });

        this.api.getRefCommission({}).subscribe(res => {
            if (res.success) {
                this.commissions = res.data;
            }
        });

        this.api.getCommissionHistory({}).subscribe(res => {
            if (res.success) {
                this.history = res.data;
            }
        });
    }

    ngOnDestroy() {
    }

    ngAfterViewInit() {
    }

    copyAddress() {
        $('#referral_url').select();
        document.execCommand('copy');

        window.getSelection().removeAllRanges();
    }
}