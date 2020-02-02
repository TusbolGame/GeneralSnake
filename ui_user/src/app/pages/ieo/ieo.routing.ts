import {Routes, RouterModule} from '@angular/router';
import {AuthLayoutComponent} from "../../layouts/auth/auth-layout.component";
import {ComingSoonComponent} from "../../shared/components/coming/coming.component";
import {IEOComponent} from "./ieo.component";

export const IEORoutes: Routes = [
    {
        path: '',
        component: IEOComponent
    },
];
