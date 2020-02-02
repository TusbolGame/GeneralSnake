import {Component, AfterViewInit, OnInit} from '@angular/core';

//Metadata
export interface RouteInfo {
    path: string;
    title: string;
    type: string;
    icon_class: string;
    collapse?: string;
    children?: ChildrenItems[];
}

export interface ChildrenItems {
    path: string;
    title: string;
    // ab: string;
    type?: string;
}

//Menu Items
export const ROUTES: RouteInfo[] = [
    {
        path: '/market',
        title: 'SIDEBAR.MARKET',
        type: 'link',
        icon_class: 'mdi-stackexchange'
    }, {
        path: '#/ieo',
        title: 'SIDEBAR.IEO_SALE',
        type: 'open',
        icon_class: 'mdi-bullseye'
    // }, {
    //     path: '#/ieo/laar',
    //     title: 'LAAR IEO Sale',
    //     type: 'open',
    //     icon_class: 'mdi-bullseye'
    }, {
        path: '/wallet',
        title: 'SIDEBAR.WALLET',
        type: 'sub',
        icon_class: 'mdi-wallet',
        children: [
            {
                path: '/wallet/balance',
                title: 'LABEL.BALANCE',
                type: 'link'
            },
            {
                path: '/wallet/deposit',
                title: 'LABEL.DEPOSIT',
                type: 'link'
            },
            {
                path: '/wallet/withdraw',
                title: 'LABEL.WITHDRAW',
                type: 'link'
            }
        ]
    // }, {
    //     path: '/ico',
    //     title: 'ICO',
    //     type: 'sub',
    //     icon_class: 'mdi-bullseye',
    //     children: [
    //         {
    //             path: '/ico/ftc',
    //             title: 'FTC',
    //             type: 'link'
    //         },
    //     ]
    }, {
        path: '/lira',
        title: 'SIDEBAR.FINANCIAL_TRANSACTION',
        type: 'sub',
        icon_class: 'mdi-currency-try',
        children: [
            {
                path: '/lira',
                title: 'SIDEBAR.TL_WALLET',
                type: 'link'
            },
            {
                path: '/usd',
                title: 'SIDEBAR.USD_WALLET',
                type: 'link'
            },
            {
                path: '/euro',
                title: 'SIDEBAR.EURO_WALLET',
                type: 'link'
            },
        ]
    },  {
        path: '/transaction',
        title: 'SIDEBAR.TRANSACTION_HISTORY',
        type: 'link',
        icon_class: 'mdi-history'
    }, {
        path: '/account',
        title: 'SIDEBAR.ACCOUNT_SETTINGS',
        type: 'link',
        icon_class: 'mdi-settings'
    // }, {
    //     path: '/ticket',
    //     title: 'SIDEBAR.TICKET',
    //     type: 'link',
    //     icon_class: 'mdi-contacts'
    }
];

declare const $: any;

@Component({
    selector: 'ap-sidebar',
    templateUrl: './sidebar.component.html',
    styleUrls: ['./sidebar.component.scss']
})

export class SidebarComponent implements OnInit,AfterViewInit {
    public menuItems: any[];

    ngOnInit() {
        this.menuItems = ROUTES.filter(menuItem => menuItem);
    }
    ngAfterViewInit() {
    }

    onClickUrl() {
        $('body').removeClass('show-sidebar');
    }
}
