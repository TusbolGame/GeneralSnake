import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import {RouterModule} from "@angular/router";
import {TransactionRoutes} from "./transaction.routing";
import {SIDEBAR_TOGGLE_DIRECTIVES} from "../../shared/sidebar.directive";
import {TransactionComponent} from "./transaction.component";
import {SharedModule} from "../../shared/shared.module";


@NgModule({
    imports: [
        CommonModule,
        RouterModule.forChild(TransactionRoutes),
        SharedModule
    ],
    declarations: [
        TransactionComponent,
    ]
})
export class TransactionModule { }
