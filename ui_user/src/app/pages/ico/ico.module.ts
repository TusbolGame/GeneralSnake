import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {RouterModule} from "@angular/router";
import {ICORoutes} from "./ico.routing";
import {SIDEBAR_TOGGLE_DIRECTIVES} from "../../shared/sidebar.directive";
import {SharedModule} from "../../shared/shared.module";
import {FTCICOComponent} from "./ftc/ftcico.component";
import {STKICOComponent} from "./stk/stkico.component";
import {MPAYICOComponent} from "./mpay/mpayico.component";
import {LAARICOComponent} from "./laar/laarico.component";


@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(ICORoutes),
        SharedModule
    ],
    declarations: [
        FTCICOComponent,
        STKICOComponent,
        MPAYICOComponent,
        LAARICOComponent
    ]
})
export class ICOModule { }
