import {NgModule, ModuleWithProviders} from '@angular/core';
import { CommonModule } from '@angular/common';
import { BrowserModule } from '@angular/platform-browser';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import {TranslateModule} from "@ngx-translate/core";
import {PasswordValidation} from "./components/password-validator.component";
import {FieldErrorDisplayComponent} from "./components/field-error-display/field-error-display.component";
import {LoaderComponent} from "./components/loader/loader.component";
import {ComingSoonComponent} from "./components/coming/coming.component";
import {ModalDialogComponent} from "./components/dialog/modal.dialog.component";
import {TableLoaderComponent} from "./components/table-loader/table-loader.component";
import {NgxDatatableModule} from "@swimlane/ngx-datatable";

@NgModule({
    exports: [
        FormsModule,
        ReactiveFormsModule,
        TranslateModule,
        NgxDatatableModule,
        FieldErrorDisplayComponent,
        LoaderComponent,
        TableLoaderComponent,
        ComingSoonComponent,
        ModalDialogComponent
    ],
    imports: [
        CommonModule,
        FormsModule,
        ReactiveFormsModule,
        TranslateModule,
        NgxDatatableModule
    ],
    declarations: [
        FieldErrorDisplayComponent,
        LoaderComponent,
        TableLoaderComponent,
        ComingSoonComponent,
        ModalDialogComponent
    ]
})

export class SharedModule {}
