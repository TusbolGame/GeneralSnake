import {Routes, RouterModule} from '@angular/router';
import {AuthLayoutComponent} from "../../layouts/auth/auth-layout.component";
import {ComingSoonComponent} from "../../shared/components/coming/coming.component";
import {AccountComponent} from "./account.component";
import {ProfileComponent} from "./profile/profile.component";
import {BankComponent} from "./bank/bank.component";
import {SecurityComponent} from "./security/security.component";
import {ReferralComponent} from "./referral/referral.component";

export const AccountRoutes: Routes = [
    {
        path: '',
        component: AccountComponent,
        children: [
            {
                path: '',
                redirectTo: 'profile'
            },
            {
                path: 'profile',
                component: ProfileComponent,
                data: {
                    title: 'TITLE.PROFILE'
                },
            },
            {
                path: 'bank',
                component: BankComponent,
                data: {
                    title: 'TITLE.BANK_ACCOUNT'
                },
            },
            {
                path: 'security',
                component: SecurityComponent,
                data: {
                    title: 'TITLE.SECURITY_SETTING'
                },
            },
            {
                path: 'referral',
                component: ReferralComponent,
                data: {
                    title: 'TITLE.REFERRAL'
                },
            },
        ]
    }
];
