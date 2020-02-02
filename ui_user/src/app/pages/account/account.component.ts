import {Component, AfterViewInit, OnInit} from '@angular/core';
import {SettingsService} from "../../services/settings/settings.service";
import {Api} from "../../services/api.service";
import {BalanceService} from "../../services/balance.service";
import {Router} from "@angular/router";
@Component({
	templateUrl: './account.component.html',
	styleUrls: ['./account.component.scss']
})
export class AccountComponent implements OnInit, AfterViewInit {
	constructor(
		public api: Api,
		public settings: SettingsService,
		public balance: BalanceService,
		public router: Router
	) {

	}

	ngOnInit() {
	}
	ngAfterViewInit(){}
}