import {Injectable} from "@angular/core";
import {Observable, Subject} from "rxjs";
import Echo from "laravel-echo";
import {Api} from "./api.service";
import {SettingsService} from "./settings/settings.service";
import {BalanceService} from "./balance.service";

declare var Pusher: any;

@Injectable()
export class PusherService {
    private echo;
    private pusher;
    private publicChannel;
    public orderObserver = new Subject();

    constructor(
        public settings: SettingsService,
        public api: Api,
        public balance: BalanceService
    ) {

    }

    connect() {
        this.echo = new Echo({
            broadcaster: 'pusher',
            key: this.settings.PUSHER_APP_KEY,
            cluster: this.settings.PUSHER_APP_CLUSTER,
            authEndpoint: this.settings.siteUrl + "/broadcasting/auth",
            auth: {
                headers: {
                    'Authorization': 'Bearer ' + this.settings.getStorage('token')
                }
            }
        });

        this.echo.private('App.User.' + this.settings.getUserSetting('id'))
            .notification((notification) => {
            });

        this.publicChannel = this.echo.join('public');

        this.publicChannel.here(() => {
            this.settings.setUserSetting('online', true);
        }).listen('.market_order', res => {
            let data = res.data;
            if (data.users.length && data.users.length > 0 && data.users.includes(this.settings.getUserSetting('id'))) {
                this.balance.getCoinBalance(data.coin);
                this.balance.getCoinBalance(data.marketCoin);
            }

            this.orderObserver.next(res.data);
        });
    }

    disconnect() {
        this.echo.leave('public');
        this.echo.leave('App.User.' + this.settings.getUserSetting('id'));
    }

    getEcho() {
        return this.echo;
    }

    getPublicChannel() {
        return this.publicChannel;
    }

    getOrderEvent(): Observable<any> {
        return this.orderObserver;
    }

}