import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {RouterModule} from "@angular/router";
import {FastBuyRoutes} from "./fastbuy.routing";
import {SIDEBAR_TOGGLE_DIRECTIVES} from "../../shared/sidebar.directive";
import {FastBuyComponent} from "./fastbuy.component";
import {SharedModule} from "../../shared/shared.module";


@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(FastBuyRoutes),
        SharedModule
    ],
    declarations: [
        FastBuyComponent
    ]
})
export class FastBuyModule { }
