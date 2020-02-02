/**
 * Created by ApolloYr on 11/18/2017.
 */

import {Injectable} from '@angular/core';

declare const $: any;

@Injectable()
export class Notifications {
    constructor() {

    }
    showNotification(header: any, message: any, type: any) {
        // const type = ['', 'info', 'success', 'warning', 'danger', 'rose', 'primary'];
        //
        $.toast({
            heading: header,
            text: message,
            position: 'top-right',
            loaderBg: '#ff6849',
            icon: type,
            hideAfter: 3000,
            stack: 6
        });
    }
}
