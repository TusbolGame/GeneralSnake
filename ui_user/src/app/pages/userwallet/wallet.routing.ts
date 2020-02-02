import {Routes, RouterModule} from '@angular/router';
import {AuthLayoutComponent} from "../../layouts/auth/auth-layout.component";
import {ComingSoonComponent} from "../../shared/components/coming/coming.component";
import {DepositComponent} from "./deposit/deposit.component";
import {WithdrawComponent} from "./withdraw/withdraw.component";
import {BalanceComponent} from "./balance/balance.component";

export const WalletRoutes: Routes = [
    {
        path: '',
        component: DepositComponent
    },
    {
        path: 'balance',
        component: BalanceComponent
    },
    {
        path: 'deposit',
        component: DepositComponent
    },
    {
        path: 'withdraw',
        component: WithdrawComponent
    }
];
