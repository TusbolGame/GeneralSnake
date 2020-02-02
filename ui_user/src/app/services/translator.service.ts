/**
 * Created by ApolloYr on 11/17/2017.
 */

import {Injectable} from '@angular/core';
import {TranslateService} from "@ngx-translate/core";
import {Api} from "./api.service";

@Injectable()
export class TranslatorService {
    private defaultLang: string = 'tr';
    private currentLang: string;

    private langs = [
        { code: 'tr', text: 'Turkish', shortName: 'TR', flagIcon: 'flag-icon-tr' },
        { code: 'en', text: 'English', shortName: 'EN', flagIcon: 'flag-icon-gb' },
        { code: 'cn', text: 'Chinese', shortName: 'CN', flagIcon: 'flag-icon-cn' },
        { code: 'es', text: 'Spanish', shortName: 'ES', flagIcon: 'flag-icon-es' },
        { code: 'ru', text: 'Russian', shortName: 'RU', flagIcon: 'flag-icon-ru' },
        { code: 'ae', text: 'Arabic', shortName: 'AE', flagIcon: 'flag-icon-ae' },
        { code: 'in', text: 'Indian', shortName: 'IN', flagIcon: 'flag-icon-in' },
        { code: 'jp', text: 'Japanese', shortName: 'JP', flagIcon: 'flag-icon-jp' },
        { code: 'kr', text: 'Korean', shortName: 'KR', flagIcon: 'flag-icon-kr' },
        { code: 'vn', text: 'Vietnamese', shortName: 'VN', flagIcon: 'flag-icon-vn' },
        { code: 'th', text: 'Thai', shortName: 'TH', flagIcon: 'flag-icon-th' }
    ];

    constructor(
        public translate: TranslateService
    ) {
        if (!translate.getDefaultLang())
            translate.setDefaultLang(this.defaultLang);

        if (window.localStorage.getItem('language') && window.localStorage.getItem('language') != '') {
            this.useLanguage(window.localStorage.getItem('language') );
        } else {
            this.useLanguage(this.defaultLang);
        }
    }

    useLanguage(lang: string = null) {
        this.currentLang = lang;
        this.translate.use(lang || this.translate.getDefaultLang());
        window.localStorage.setItem('language', lang);
    }

    getLanguages() {
        return this.langs;
    }

    getCurrentLang() {
        for (var i = 0; i < this.langs.length; i ++) {
            if (this.langs[i].code == this.currentLang) {
                return this.langs[i];
            }
        }
        return {};
    }

    getCurrentLangCode() {
        return this.currentLang;
    }
}

