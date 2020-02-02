import {Routes, RouterModule} from '@angular/router';
import {AuthLayoutComponent} from "../../layouts/auth/auth-layout.component";
import {ComingSoonComponent} from "../../shared/components/coming/coming.component";
import {MPAYICOComponent} from "./mpay/mpayico.component";
import {LAARICOComponent} from "./laar/laarico.component";

export const ICORoutes: Routes = [
    {
        path: '',
        component: MPAYICOComponent
    },
    {
        path: 'mpay',
        component: MPAYICOComponent
    },
    {
        path: 'laar',
        component: LAARICOComponent
    }
];
