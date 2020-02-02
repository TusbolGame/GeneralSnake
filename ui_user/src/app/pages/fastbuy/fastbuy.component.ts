import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {SettingsService} from "../../services/settings/settings.service";
import {Api} from "../../services/api.service";
import {BalanceService} from "../../services/balance.service";
import {MarketApi} from "../../services/marketApi.service";
import {Validate} from "../../services/validate.service";
import {Notifications} from "../../services/notifications.service";
@Component({
	templateUrl: './fastbuy.component.html',
	styleUrls: ['./fastbuy.component.scss']
})
export class FastBuyComponent implements OnInit, AfterViewInit, OnDestroy {
	tl_pairs: any = [];
	btc_pairs: any = [];
	pairs: any = [];

	selMarketCoin = 'TL';
	selCoin = 'BTC';
	selPair: any = {
		places: 6,
		fastbuy_fee: 1
	};

	fastbuyOrders: any = [];

	fastbuy: any = {
		rate: 0,
		amount: 0
	};

	timerID = null;
	constructor(
		public marketApi: MarketApi,
		public settings: SettingsService,
		public balance: BalanceService,
		public validate: Validate,
		public notify: Notifications
	) {

	}

	ngOnInit() {
		if (window.localStorage.getItem('fastbuy_coin') && window.localStorage.getItem('fastbuy_coin') != '') {
			this.selCoin = window.localStorage.getItem('fastbuy_coin');
		}

		this.marketApi.getCoinPairInfo({}).subscribe(res => {
			if (res.success) {
				this.tl_pairs = res.data.filter(pair => pair.market_coin === 'TL');

				this.pairs = this.tl_pairs;
				this.selPair = res.data.filter(pair => pair.coin === this.selCoin)[0];
			}
		});

		this.loadFastBuyList();
		this.loadFastBuyRate();

		let _parent = this;
		this.timerID = setInterval(function() {
			_parent.loadFastBuyRate();
		}, 3000);
	}

	ngOnDestroy() {
		clearInterval(this.timerID);
	}

	onOrderEvent(data) {
		if (data.last_price && data.last_price != '' && data.last_price > 0) {
			if (data.marketCoin == 'TL') {
				let pair = this.tl_pairs.filter(pair => pair.coin == data.coin)[0];
				pair.last_price = data.last_price;
				pair.change = pair.first_price > 0 ? (pair.last_price - pair.first_price) * 100 / pair.first_price : 0;
				if (pair.last_price > pair.high) {
					pair.high = pair.last_price;
				}
				if (pair.last_price < pair.low) {
					pair.low = pair.last_price;
				}
				pair.volume = Number(pair.volume) + (Number(data.volume) > 0 ? Number(data.volume) : 0);
			}
		}
	}

	loadFastBuyList() {
		this.marketApi.getFastBuyList(this.selCoin, {
			limit_count: 20
		}).subscribe( res => {
			if (res.success) {
				this.fastbuyOrders = res.data;
			}
		});
	}

	loadFastBuyRate() {
		this.marketApi.getFastBuyRate(this.selCoin, {}).subscribe( res=> {
			if (res.success) {
				this.fastbuy.rate = res.rate;
			}
		});
	}

	onClickPair(pair) {
		if (pair.coin == this.selCoin) {
			return;
		}

		this.selCoin = pair.coin;
		this.selPair = pair;
		this.fastbuy.amount = 0;

		window.localStorage.setItem('fastbuy_coin', this.selCoin);

		this.loadFastBuyList();
		this.loadFastBuyRate();
	}

	fastbuyAll() {
		if (this.fastbuy.rate == 0) {
			this.fastbuy.amount = 0;
		} else {
			this.fastbuy.amount = this.validate.round(Number(this.settings.getBalance('TL') / this.fastbuy.rate), 8);
		}
	}



	submitFastBuy() {
		if (this.fastbuy.rate == 0 || this.fastbuy.amount == 0) {
			this.notify.showNotification('Warning', 'Please enter amount', 'warning');
			return;
		}

		this.settings.loading = true;
		this.marketApi.fastBuy(this.selCoin, this.fastbuy).subscribe( res => {
			this.settings.loading = false;
			if (res.success) {
				this.notify.showNotification('Success', 'You bought '+ this.selCoin + 'successfully.', 'success');

				this.balance.getCoinBalance(this.selMarketCoin);
				this.balance.getCoinBalance(this.selCoin);

				this.loadFastBuyList();
			} else {
				this.notify.showNotification('Warning', res.error, 'warning');
			}
		}, err => {
			this.settings.loading = false;
			this.notify.showNotification('Error', 'Please try again', 'error');
		});
	}

	ngAfterViewInit(){}
}