import {Routes, RouterModule} from '@angular/router';
import {AuthLayoutComponent} from "../../layouts/auth/auth-layout.component";
import {ComingSoonComponent} from "../../shared/components/coming/coming.component";
import {TransactionComponent} from "./transaction.component";

export const TransactionRoutes: Routes = [
    {
        path: '',
        component: TransactionComponent
    }
];
