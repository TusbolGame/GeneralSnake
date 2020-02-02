import {Component, OnInit, AfterViewInit, OnDestroy, AfterContentInit} from '@angular/core';
import {ActivatedRoute, ActivatedRouteSnapshot, Router} from '@angular/router';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {TranslatorService} from "../../services/translator.service";
import {Api} from "../../services/api.service";

declare const $: any;
declare const window: any;
@Component({
    selector: 'app-home',
    templateUrl: './ucretler.component.html',
    styleUrls: ['./ucretler.component.scss']
})
export class UcretlerComponent implements OnInit, AfterViewInit, OnDestroy {
    coins: any = [];
    constructor(public router: Router,
                public translator: TranslatorService,
                public api: Api
    ) {
        // if (this.router.url.includes('/confirmed')) {
        //     this.confirmed_user_email = activatedRouteSnapshot.params['user_id'];
        // }
    }

    ngOnInit() {
        this.api.getCoin({
            only_coin: true
        }).subscribe(res => {
            if (res.success) {
                this.coins = res.data;
            }
        });
    }

    ngOnDestroy() {
    }

    ngAfterViewInit() {

        $(function () {
            $(".preloader").fadeOut();
        });
        $(function () {
            (<any>$('[data-toggle="tooltip"]')).tooltip()
        });
    }

}
