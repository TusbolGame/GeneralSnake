import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {RouterModule} from "@angular/router";
import {AccountRoutes} from "./account.routing";
import {SIDEBAR_TOGGLE_DIRECTIVES} from "../../shared/sidebar.directive";
import {SharedModule} from "../../shared/shared.module";
import {AccountComponent} from "./account.component";
import {ProfileComponent} from "./profile/profile.component";
import {BankComponent} from "./bank/bank.component";
import {SecurityComponent} from "./security/security.component";
import {ReferralComponent} from "./referral/referral.component";


@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(AccountRoutes),
        SharedModule
    ],
    declarations: [
        AccountComponent,
        ProfileComponent,
        BankComponent,
        SecurityComponent,
        ReferralComponent
    ]
})
export class AccountModule { }
