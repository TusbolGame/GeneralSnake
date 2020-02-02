/**
 * Created by ApolloYr on 11/17/2017.
 */

import {Injectable} from '@angular/core';
import 'rxjs/add/operator/catch';
import {SettingsService} from './settings/settings.service';
import {Router} from '@angular/router';
import {HttpClient, HttpHeaders, HttpParams} from "@angular/common/http";
import * as _ from 'lodash';
import {Observable} from "rxjs";
import {TranslatorService} from "./translator.service";

@Injectable()
export class Api {
    constructor(private http: HttpClient,
                private router: Router,
                public settings: SettingsService,
                public translator: TranslatorService
    ) {
    }

    createAuthorizationHeader() {
        return new HttpHeaders().set('Authorization', 'Bearer ' + this.settings.getStorage('token'));
    }

    get(url, data?) {
        let headers = this.createAuthorizationHeader();

        data.locale = this.translator.getCurrentLangCode();
        return this.http.get(this.settings.apiUrl + url, {
            headers: headers,
            params: data
        }).map(res => res).catch((error: any) => this.handleError(this, error));
    }

    post(url, data) {
        let headers = this.createAuthorizationHeader();

        data.locale = this.translator.getCurrentLangCode();
        return this.http.post(this.settings.apiUrl + url, data, {
            headers
        }).map(res => res).catch((error: any) => this.handleError(this, error));
    }

    put(url, data) {
        let headers = this.createAuthorizationHeader();

        data.locale = this.translator.getCurrentLangCode();
        return this.http.put(this.settings.apiUrl + url, data, {
            headers: headers
        }).map(res => res).catch((error: any) => this.handleError(this, error));
    }

    handleError(_parent, error: any) {
        console.log(error);
        if ((error.status == 401 || error.status == 400) && error.url && !error.url.endsWith('/login')) {
            console.log('unauthorized');
            if (_parent.settings) _parent.settings.setStorage('token', false);
            if (_parent.settings) _parent.settings.setAppSetting('is_loggedin', false);
            _parent.router.navigate(['/']);
        }
        // In a real world app, you might use a remote logging infrastructure

        return Observable.throw(error);
    }

    uploadFile(file) {
        let headers = this.createAuthorizationHeader();

        var formData = new FormData();

        formData.append("file", file, file.name);

        return this.http.post(this.settings.apiUrl + '/upload/file', formData, {
            headers: headers
        }).map(res => res).catch((error: any) => this.handleError(this, error));
    }

    getAccountInfo() {
        return this.get('/account', {});
    }

    login(data): any {
        return this.post('/login', data);
    }

    logout(data): any {
        return this.post('/account/logout', data);
    }

    confirmPassword(data): any {
        return this.post('/confirm_password', data);
    }

    resetPassword(data) {
        return this.post('/reset_password', data);
    }

    register(data): any {
        return this.post('/register', data);
    }

    changePassword(data) {
        return this.post('/change_password', data);
    }

    sendForgotEmail(data) {
        return this.post('/account/sendForgotEmail', data);
    }

    sendActivateEmail(data) {
        return this.post('/account/sendActivateEmail', data);
    }

    confirmResetCode(data) {
        return this.post('/confirm_resetcode', data);
    }

    getSystemSettings(data) {
        return this.get('/settings/all', data);
    }

    getSettings(data) {
        return this.get('/settings', data);
    }

    confirmUser(data) {
        return this.post('/admin/user/confirm', data);
    }

    confirmCodeAndLogin(data) {
        return this.post('/user/confirm/2fcode', data);
    }

    generateUserG2FSecurityCode(data) {
        return this.get('/user/g2fcode/generate', data);
    }
    confirmUserG2FCode(data) {
        return this.post('/user/g2fcode/confirm', data);
    }
    enableUserG2FCode(data) {
        return this.post('/user/g2fcode/enable', data);
    }
    getUser(data) {
        return this.get('/user', data);
    }
    getUserByRefCode(data) {
        return this.get('/refcode/user', data);
    }
    sendUserSMSCode(data) {
        return this.post('/user/send/sms', data);
    }
    confirmUserSMSCode(data) {
        return this.post('/user/confirm/sms', data);
    }
    sendUserEmailCode(data) {
        return this.post('/user/send/email', data);
    }
    confirmUserEmailCode(data) {
        return this.post('/user/confirm/email', data);
    }

    verifyUserProfile(data) {
        return this.post('/admin/user/profile/verify', data);
    }

    updateUserProfile(data) {
        return this.post('/admin/user/profile/update', data);
    }

    // set Locale
    setLocale(lang) {
        return this.post('/locale/' + lang, {});
    }
    // coin
    getCoin(data) {
        return this.get('/coins', data);
    }
    // profile
    getProfile(data) {
        return this.get('/profile', data);
    }
    saveProfile(data) {
        return this.post('/profile', data);
    }

    // Referral
    getReferral(data) {
        return this.get('/account/referral', data);
    }
    getRefCommission(data) {
        return this.get('/account/referral/commission', data);
    }
    getCommissionHistory(data) {
        return this.get('/account/referral/commission/history', data);
    }

    // Ticket
    getMyTicket(data) {
        return this.get('/ticket', data);
    }
    getAllTicket(data) {
        return this.get('/ticket/all', data);
    }
    getTicketDetail(data) {
        return this.get('/ticket/detail', data);
    }
    postTicket(data) {
        return this.post('/ticket', data);
    }
    replyTicket(data) {
        return this.post('/ticket/reply', data);
    }
    closeTicket(data) {
        return this.post('/ticket/close', data);
    }

    // Security
    generateNewG2FSecurityCode(data) {
        return this.get('/security/g2fcode/generate', data);
    }
    confirmG2FCode(data) {
        return this.post('/security/g2fcode/confirm', data);
    }
    sendSMSCode(data) {
        return this.post('/security/send/sms', data);
    }
    confirmSMSCode(data) {
        return this.post('/security/smscode/confirm', data);
    }

    sendEmailCode(data) {
        return this.post('/security/send/email', data);
    }
    confirmEmailCode(data) {
        return this.post('/security/email/confirm', data);
    }
    // Market Rate
    getMarketPrice(data) {
        return this.get('/market/price', data);
    }

    // Bank
    getBanks(data) {
        return this.get('/bank', data);
    }
    saveBank(data) {
        return this.post('/bank', data);
    }
    deleteBank(data) {
        return this.post('/bank/delete', data);
    }

    // Currency Rate
    getCurrencyRate() {
        return this.get('/currency_rate', {});
    }

    //
    getAccountBalance() {
        return this.get('/account/balance', {});
    }
}

