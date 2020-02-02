import {Component, OnInit, AfterViewInit, OnDestroy, AfterContentInit} from '@angular/core';
import {ActivatedRoute, ActivatedRouteSnapshot, Router} from '@angular/router';
import {FormBuilder, FormGroup, Validators} from "@angular/forms";
import {TranslatorService} from "../../services/translator.service";

declare const $: any;
declare const window: any;
@Component({
    selector: 'app-home',
    templateUrl: './buycoin.component.html',
    styleUrls: ['./buycoin.component.scss']
})
export class BuycoinComponent implements OnInit, AfterViewInit, OnDestroy {
    constructor(public router: Router,
                public translator: TranslatorService
    ) {
        // if (this.router.url.includes('/confirmed')) {
        //     this.confirmed_user_email = activatedRouteSnapshot.params['user_id'];
        // }
    }

    ngOnInit() {
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
