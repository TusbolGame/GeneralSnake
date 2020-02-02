import {Routes, RouterModule} from '@angular/router';
import {AuthLayoutComponent} from "../layouts/auth/auth-layout.component";
import {TLTradingComponent} from "./tltrading/tltrading.component";
import {TicketComponent} from "./ticket/ticket.component";
import {EUROTradingComponent} from "./eurotrading/eurotrading.component";
import {USDTradingComponent} from "./usdtrading/usdtrading.component";

export const PageRoutes: Routes = [
    {
        path: '',
        component: AuthLayoutComponent,
        children: [
            {
                path: 'market',
                loadChildren: './market/market.module#MarketModule'
            },
            {
                path: 'wallet',
                loadChildren: './userwallet/wallet.module#WalletModule'
            },
            {
                path: 'lira',
                component: TLTradingComponent
            },
            {
                path: 'usd',
                component: USDTradingComponent
            },
            {
                path: 'usd/epay/success',
                component: USDTradingComponent
            },
            {
                path: 'usd/epay/fail',
                component: USDTradingComponent
            },
            {
                path: 'euro',
                component: EUROTradingComponent
            },
            {
                path: 'euro/epay/success',
                component: EUROTradingComponent
            },
            {
                path: 'euro/epay/fail',
                component: EUROTradingComponent
            },
            {
                path: 'transaction',
                loadChildren: './transaction/transaction.module#TransactionModule'
            },
            {
                path: 'account',
                loadChildren: './account/account.module#AccountModule'
            },
            // {
            //     path: 'ticket',
            //     data: {
            //         title: 'TITLE.TICKET'
            //     },
            //     component: TicketComponent
            // },
        ]
    }
];
