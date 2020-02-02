/**
 * Created by ApolloYr on 11/18/2017.
 */

import {NgModule} from '@angular/core';
import {SettingsService} from "./settings/settings.service";
import {SecureHttpService} from "./securehttp.service";
import {Api} from "./api.service";
import {AuthGuard} from "./authguard.service";
import {Notifications} from "./notifications.service";
import {Validate} from "./validate.service";
import {BalanceService} from "./balance.service";
import {PusherService} from "./pusher.service";
import {SystemService} from "./system.service";
import {TranslatorService} from "./translator.service";
import {MarketApi} from "./marketApi.service";
import {CoinApi} from "./coinApi.service";
import {AdminApi} from "./adminApi.service";
import {IcoApi} from "./icoApi.service";
import {Base58Service} from "./base58.service";
import {IeoApi} from "./ieoApi.service";

@NgModule({
    imports: [],
    declarations: [],
    providers: [
        SettingsService,
        SecureHttpService,
        Api,
        AuthGuard,
        Notifications,
        Validate,
        BalanceService,
        PusherService,
        SystemService,
        TranslatorService,
        MarketApi,
        CoinApi,
        AdminApi,
        IcoApi,
        IeoApi,
        Base58Service
    ],
    exports: []
})
export class ServicesModule {
}
