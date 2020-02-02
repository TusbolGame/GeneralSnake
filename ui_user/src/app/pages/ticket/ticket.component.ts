import {Component, AfterViewInit, OnInit} from '@angular/core';
import {Api} from "../../services/api.service";
import {SettingsService} from "../../services/settings/settings.service";
import {Notifications} from "../../services/notifications.service";
import {BalanceService} from "../../services/balance.service";

declare var $: any;
declare var swal: any;

@Component({
    templateUrl: './ticket.component.html',
    styleUrls: ['./ticket.component.scss']
})
export class TicketComponent implements OnInit, AfterViewInit {
    tickets: any = [];
    details: any = [];

    isNew: boolean = false;
    isReply: boolean = false;

    ticket: any = {};
    constructor(public api: Api,
                public settings: SettingsService,
                public notify: Notifications,
                public balance: BalanceService) {

    }

    ngOnInit() {
        this.loadMyTicket();
    }

    ngAfterViewInit() {
    }

    loadMyTicket() {
        this.api.getMyTicket({}).subscribe( res=> {
            if (res.success) {
                this.tickets = res.data;
            }
        });
    }

    submitTicket() {
        this.isNew = true;
        this.isReply = false;
        this.ticket = {};
    }

    onReply() {
        this.ticket.message = '';
        this.ticket.attach_url = '';
        this.ticket.attach_name = '';

        this.isNew = false;
        this.isReply = true;
    }

    showTicket(ticket) {
        this.ticket = ticket;
        this.isNew = false;
        this.isReply = false;

        this.settings.loading = true;
        this.api.getTicketDetail({
            ticket_id: ticket.id
        }).subscribe( res=> {
            this.settings.loading = false;
            if (res.success) {
                this.details = res.data;
            } else {
                this.notify.showNotification('Warning', res.error, 'warning');
            }
        }, err=> {
            this.settings.loading = false;
            this.notify.showNotification('Error', 'You can not see detail.', 'error');
        });
    }

    sendTicket() {
        if (this.isNew) {
            this.settings.loading = true;
            this.api.postTicket(this.ticket).subscribe( res => {
                this.settings.loading = false;
                if (res.success) {
                    this.notify.showNotification('Success', 'Your Ticket was posted successfully', 'success');
                    this.loadMyTicket();

                    this.isNew = false;
                    this.ticket = {};
                } else {
                    this.notify.showNotification('Warning', res.error, 'warning');
                }
            }, err => {
                this.settings.loading = false;
                this.notify.showNotification('Error', 'Please try again.', 'error');
            });
        }
        else if (this.isReply) {
            this.settings.loading = true;
            this.api.replyTicket(this.ticket).subscribe( res => {
                this.settings.loading = false;
                if (res.success) {
                    this.notify.showNotification('Success', 'Your Reply Ticket was posted successfully', 'success');

                    this.isReply = false;
                    this.showTicket(this.ticket);
                } else {
                    this.notify.showNotification('Warning', res.error, 'warning');
                }
            }, err => {
                this.settings.loading = false;
                this.notify.showNotification('Error', 'Please try again.', 'error');
            });
        }
    }

    fileChangeListener($event) {
        let image: any = new Image();
        let file: File = $event.target.files[0];

        let filename = file.name;
        let extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();

        if (extension == 'gif' || extension == 'png' || extension == 'bmp' || extension == 'jpeg' || extension == 'jpg') {
            this.settings.loading = true;
            this.api.uploadFile(file).subscribe(res => {
                this.settings.loading = false;
                if (res.success) {
                    this.ticket.attach_url = res.url;
                    this.ticket.attach_name = file.name;
                }
            }, err => {
                this.settings.loading = false;
            });
        } else {
            this.notify.showNotification('Warning', 'Please upload only image file', 'warning');
        }
    }
}