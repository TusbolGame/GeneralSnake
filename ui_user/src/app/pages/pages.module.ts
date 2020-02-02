import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { SIDEBAR_TOGGLE_DIRECTIVES } from '../shared/sidebar.directive';
import { NavigationComponent } from '../shared/header-navigation/navigation.component';
import { SidebarComponent } from '../shared/sidebar/sidebar.component';
import { RightSidebarComponent } from '../shared/right-sidebar/rightsidebar.component';
import {AuthLayoutComponent} from "../layouts/auth/auth-layout.component";
import {RouterModule} from "@angular/router";
import {PageRoutes} from "./pages.routing";
import {SharedModule} from "../shared/shared.module";
import {TLTradingComponent} from "./tltrading/tltrading.component";
import {TicketComponent} from "./ticket/ticket.component";
import {USDTradingComponent} from "./usdtrading/usdtrading.component";
import {EUROTradingComponent} from "./eurotrading/eurotrading.component";

@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(PageRoutes),
        SharedModule
    ],
    declarations: [
        NavigationComponent,
        SidebarComponent,
        RightSidebarComponent,
        AuthLayoutComponent,
        TLTradingComponent,
        USDTradingComponent,
        EUROTradingComponent,
        TicketComponent,
        SIDEBAR_TOGGLE_DIRECTIVES
    ]
})
export class PagesModule { }
