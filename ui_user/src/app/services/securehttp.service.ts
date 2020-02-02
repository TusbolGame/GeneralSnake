import { Injectable } from '@angular/core';
import {
    Http, ConnectionBackend, Headers, RequestOptions, Request, Response, RequestOptionsArgs,
    XHRBackend
} from '@angular/http';
import { Observable } from 'rxjs/Observable';

@Injectable()
export class SecureHttpService extends Http {
    constructor(backend: ConnectionBackend, defaultOptions: RequestOptions) {
        super(backend, defaultOptions);
    }

    request(url: string | Request, options?: RequestOptionsArgs): Observable<Response> {

        return super.request(url, options);
    }

    get(url: string, options?: RequestOptionsArgs): Observable<Response> {
        // if (url.indexOf('/api')) this.events.publish('ajax:start');

        return super.get(url, options).map(res => {
            if (url.indexOf('/api')) {
                // this.events.publish('ajax:stop');
                return res.json();
            }

            return res;
        }).catch(error => {
            return this.handleError(url, error);
        });
    }

    post(url: string, options?: RequestOptionsArgs): Observable<Response> {
        // if (url.indexOf('/api')) this.events.publish('ajax:start');

        return super.post(url, options).map(res => {
            // if (url.indexOf('/api')) {
            //     this.events.publish('ajax:stop');
            //     return res.json();
            // }

            return res;
        }).catch(error => {
            return this.handleError(url, error);
        });
    }

    private handleError (url:string, error: Response | any) {

        // if (url.indexOf('/api')) {
        //     this.events.publish('ajax:stop');
        //     this.events.publish('toast:presented', 'Sorry. error occured while connecting server');
        // }

        // return new Observable(observer => {
        //     observer.complete();
        // });
        // In a real world app, you might use a remote logging infrastructure
        let errMsg: string;
        if (error instanceof Response) {
            const body = error.json() || '';
            const err = body.error || JSON.stringify(body);
            errMsg = `${error.status} - ${error.statusText || ''} ${err}`;
        } else {
            errMsg = error.message ? error.message : error.toString();
        }

        return Observable.throw(errMsg);
    }
}

export function  SecureHttpProvider(backend: XHRBackend, defaultOptions:RequestOptions) {
    return new SecureHttpService(backend, defaultOptions);
}