import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {RouterModule} from "@angular/router";
import {SIDEBAR_TOGGLE_DIRECTIVES} from "../../shared/sidebar.directive";
import {SharedModule} from "../../shared/shared.module";
import {WalletRoutes} from "./wallet.routing";
import {DepositComponent} from "./deposit/deposit.component";
import {WithdrawComponent} from "./withdraw/withdraw.component";
import {BalanceComponent} from "./balance/balance.component";


@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(WalletRoutes),
        SharedModule
    ],
    declarations: [
        BalanceComponent,
        DepositComponent,
        WithdrawComponent
    ]
})

export class WalletModule { }
