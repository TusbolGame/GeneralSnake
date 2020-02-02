import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {RouterModule} from "@angular/router";
import {SIDEBAR_TOGGLE_DIRECTIVES} from "../../shared/sidebar.directive";
import {SharedModule} from "../../shared/shared.module";
import {IEORoutes} from "./ieo.routing";
import {IEOComponent} from "./ieo.component";


@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(IEORoutes),
        SharedModule
    ],
    declarations: [
        IEOComponent
    ]
})
export class IEOModule { }
