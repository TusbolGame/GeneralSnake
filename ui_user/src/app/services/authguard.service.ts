/**
 * Created by ApolloYr on 11/17/2017.
 */
import {Injectable} from '@angular/core';
import {Router, Resolve, RouterStateSnapshot, ActivatedRouteSnapshot} from '@angular/router';
import {SettingsService} from './settings/settings.service';
import {Api} from './api.service';
import {BalanceService} from "./balance.service";
import {PusherService} from "./pusher.service";
import {SystemService} from "./system.service";

@Injectable()
export class AuthGuard implements Resolve<any> {

    constructor(private router: Router,
                private settings: SettingsService,
                private api: Api,
                private balance: BalanceService,
                private pusherService: PusherService,
                private system: SystemService
    ) {
    }

    resolve(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Promise<any> {
        return new Promise((resolve, reject) => {
            if (this.settings.getUserSetting('email')) {
                this.canAccess(state.url, this.settings.getUserSetting('role')) ? resolve(true) : reject('no privillege');
            } else if (this.settings.getStorage('token')) {
                this.api.getAccountInfo().subscribe(res => {
                    if (!this.canAccess(state.url, res.role)) {
                        reject('no privillege');
                    } else {
                        this.settings.setAppSetting('is_loggedin', true);
                        this.settings.user = res;

                        if (res.role != 'admin' && res.role != 'superadmin') {
                            this.system.init();
                        }

                        this.pusherService.connect();

                        resolve(true);
                    }
                }, err => {
                    reject('information is invalid');
                    this.settings.setStorage('token', false);
                    this.router.navigate(['/login']);
                });
            } else {
                reject('not logged in');
                this.router.navigate(['/login']);
            }
        });
    }

    private canAccess(url, role) {
        if (url.startsWith('/admin') && (role == 'admin' || role == 'superadmin')) {
            return true;
        } else if (url.startsWith('/admin') && role != 'admin' && role != 'superadmin') {
            return false;
        } else {
            return true;
        }
    }
}

