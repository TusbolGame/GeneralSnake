import * as $ from 'jquery';
import {BrowserModule} from '@angular/platform-browser';
import {NgModule} from '@angular/core';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {AuthGuard} from './shared/guard/auth.guard';
import {AppRoutes} from './app.routing';
import {AppComponent} from './app.component';
import {SharedModule} from "./shared/shared.module";
import {TranslateLoader, TranslateModule} from "@ngx-translate/core";
import {TranslateHttpLoader} from "@ngx-translate/http-loader";
import {HttpClient, HttpClientModule} from "@angular/common/http";
import {ServicesModule} from "./services/services.module";
import {RouterModule} from "@angular/router";
import {SignupComponent} from "./signup/signup.component";
import {BrowserAnimationsModule} from "@angular/platform-browser/animations";
import {ResetPasswordComponent} from "./reset_password/reset_password.component";
import {ResetPasswordResolver} from "./reset_password/reset_password-resolver.service";
import {HomeComponent} from "./home/home.component";
import {BuycoinComponent} from "./footer/buycoin/buycoin.component";
import {OverviewComponent} from "./footer/overview/overview.component";
import {SellcoinComponent} from "./footer/sellcoin/sellcoin.component";
import {ContactUsComponent} from "./footer/contact/contact.component";
import {UcretlerComponent} from "./footer/ucretler/ucretler.component";
import {TermComponent} from "./footer/term/term.component";

export function createTranslateLoader(http: HttpClient) {
    return new TranslateHttpLoader(http, './assets/i18n/', '.json');
}

@NgModule({
    declarations: [
        AppComponent,
        SignupComponent,
        ResetPasswordComponent,
        HomeComponent,
        BuycoinComponent,
        OverviewComponent,
        SellcoinComponent,
        TermComponent,
        ContactUsComponent,
        UcretlerComponent
    ],
    imports: [
        BrowserModule,
        HttpClientModule,
        FormsModule,
        ReactiveFormsModule,
        RouterModule.forRoot(AppRoutes, { useHash: true }),
        SharedModule,
        TranslateModule.forRoot({
            loader: {
                provide: TranslateLoader,
                useFactory: (createTranslateLoader),
                deps: [HttpClient]
            }
        }),
        ServicesModule,
        BrowserAnimationsModule
    ],
    providers: [
        AuthGuard,
        ResetPasswordResolver
    ],
    bootstrap: [AppComponent]
})
export class AppModule {
}
