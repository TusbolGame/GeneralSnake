import {Routes, RouterModule} from '@angular/router';
import {FastBuyComponent} from "./fastbuy.component";

export const FastBuyRoutes: Routes = [
    {
        path: '',
        component: FastBuyComponent
    },
    {
        path: '**',
        redirectTo: '',
    }
];
