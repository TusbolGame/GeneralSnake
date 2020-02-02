import {Component, AfterViewInit, OnInit} from '@angular/core';
import {SettingsService} from "../../services/settings/settings.service";
import {BalanceService} from "../../services/balance.service";
import {Router} from "@angular/router";
import {CoinApi} from "../../services/coinApi.service";

declare const $: any;

@Component({
	templateUrl: './transaction.component.html',
	styleUrls: ['./transaction.component.scss']
})
export class TransactionComponent implements OnInit, AfterViewInit {
	selCoin = 'BTC';
	selCoinInfo: any = {
		fullname: ''
	};
	coins: any = [];

	history: any = [];

	loading = false;

	datatable = null;
	constructor(
		public coinApi: CoinApi,
		public settings: SettingsService,
		public balance: BalanceService,
		public router: Router
	) {

	}

	ngOnInit() {
		if (window.localStorage.getItem('transaction_coin') && window.localStorage.getItem('transaction_coin') != '') {
			this.selCoin = window.localStorage.getItem('transaction_coin');
		}

		this.coinApi.getCoin({
		}).subscribe(res => {
			if (res.success) {
				this.coins = res.data;
				this.selCoinInfo = this.coins.filter(coin => coin.coin === this.selCoin)[0];
			}
		}, err => {

		});

		this.loadTxHistory();
	}

	loadTxHistory() {
		this.loading = true;
		if (this.datatable) {
			this.datatable.destroy();
			this.datatable = null;
		}

		this.coinApi.getTxHistory(this.selCoin, {}).subscribe( res => {
			this.loading = false;
			if (res.success) {
				this.history = res.data;

				let _parent = this;
				if (res.data.length > 0) {
					var timer = setInterval(function () {
						if (res.data.length == $('#history tbody tr').length) {
							_parent.datatable = $('#history').DataTable();
							clearInterval(timer);
						}
					}, 100);
				} else {
					// _parent.datatable = $('#history').DataTable();
				}
			}
		}, err => {
			this.loading = false;
		});
	}
	onClickCoin(coin) {
		if (coin == this.selCoin) {
			return;
		}

		this.selCoin = coin;
		this.selCoinInfo = this.coins.filter(coin => coin.coin == this.selCoin)[0];

		this.loadTxHistory();

		window.localStorage.setItem('transaction_coin', this.selCoin);
	}

	ngAfterViewInit() {
		$('.select2').select2();

		let _parent = this;
		$('.select2').on('select2:select', function (e) {
			_parent.onClickCoin(e.params.data.id);
		});
	}
}