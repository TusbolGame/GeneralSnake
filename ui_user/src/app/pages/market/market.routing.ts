import {Routes, RouterModule} from '@angular/router';
import {MarketComponent} from "./market.component";

export const MarketRoutes: Routes = [
    {
        path: '',
        component: MarketComponent
    },
    { path: '**', redirectTo: '' }
];
