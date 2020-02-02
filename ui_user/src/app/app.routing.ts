import { Routes } from '@angular/router';
import {SignupComponent} from "./signup/signup.component";
import {AuthGuard} from "./services/authguard.service";
import {ResetPasswordComponent} from "./reset_password/reset_password.component";
import {ResetPasswordResolver} from "./reset_password/reset_password-resolver.service";
import {HomeComponent} from "./home/home.component";
import {BuycoinComponent} from "./footer/buycoin/buycoin.component";
import {OverviewComponent} from "./footer/overview/overview.component";
import {SellcoinComponent} from "./footer/sellcoin/sellcoin.component";
import {ContactUsComponent} from "./footer/contact/contact.component";
import {TermComponent} from "./footer/term/term.component";
import {UcretlerComponent} from "./footer/ucretler/ucretler.component";

export const AppRoutes: Routes = [
    {
        path: '', component: HomeComponent
    },
    {
        path: '',
        loadChildren: './pages/pages.module#PagesModule',
        resolve: {
            AuthGuard
        }
    },
    // {
    //     path: 'ieo',
    //     loadChildren: './pages/ico/ico.module#ICOModule',
    //     resolve: {
    //         AuthGuard
    //     }
    // },
    {
        path: 'ieo',
        loadChildren: './pages/ieo/ieo.module#IEOModule',
        resolve: {
            AuthGuard
        }
    },
    { path: 'login', component: HomeComponent  },
    { path: 'confirmed/:email', component: HomeComponent  },
    { path: 'ref/:id', component: HomeComponent  },
    {
        path: 'reset_password/:code',
        component: ResetPasswordComponent
    },
    { path: 'buycoin', component: BuycoinComponent },
    { path: 'overview', component: OverviewComponent },
    { path: 'sellcoin', component: SellcoinComponent },
    { path: 'contactus', component: ContactUsComponent },
    { path: 'term', component: TermComponent },
    { path: 'ucretler', component: UcretlerComponent },
    { path: '404', loadChildren: './404/not-found.module#NotFoundModule' },
    { path: '**', redirectTo: '404' }
];
