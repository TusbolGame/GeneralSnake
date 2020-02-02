import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {RouterModule} from "@angular/router";
import {MarketRoutes} from "./market.routing";
import {SIDEBAR_TOGGLE_DIRECTIVES} from "../../shared/sidebar.directive";
import {MarketComponent} from "./market.component";
import {SharedModule} from "../../shared/shared.module";
import {ChartModule, HIGHCHARTS_MODULES} from "angular-highcharts";


import more from 'highcharts/highcharts-more.src';
import exporting from 'highcharts/modules/exporting.src';
import highstock from 'highcharts/modules/stock.src';

export function highchartsModules() {
    return [more, exporting, highstock];
}

@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(MarketRoutes),
        SharedModule,
        ChartModule
    ],
    declarations: [
        MarketComponent,
    ],
    providers: [
        {
            provide: HIGHCHARTS_MODULES,
            useFactory: highchartsModules
        }
    ]
})
export class MarketModule { }
