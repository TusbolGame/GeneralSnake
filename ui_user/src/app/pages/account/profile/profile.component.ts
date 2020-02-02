import {Component, AfterViewInit, OnInit, OnDestroy} from '@angular/core';
import {Api} from "../../../services/api.service";
import {SettingsService} from "../../../services/settings/settings.service";
import {BalanceService} from "../../../services/balance.service";
import {Notifications} from "../../../services/notifications.service";
import {PusherService} from "../../../services/pusher.service";
import {Subject} from "rxjs";
import {takeUntil} from "rxjs/operators";
import {SystemService} from "../../../services/system.service";

declare const swal: any;
@Component({
    templateUrl: './profile.component.html',
    styleUrls: ['./profile.component.scss']
})
export class ProfileComponent implements OnInit, AfterViewInit, OnDestroy {

    profile: any = {};

    constructor(public api: Api,
                public settings: SettingsService,
                public balance: BalanceService,
                public notify: Notifications,
                public pusher: PusherService,
                public system: SystemService
    ) {


    }

    ngOnInit() {
        this.api.getProfile({}).subscribe(res => {
            if (res.success) {
                this.profile = res.data;

                if (!this.profile.verify_status) {
                    this.profile.verify_status = 0;
                }

            }
        });
    }

    ngOnDestroy() {
    }

    ngAfterViewInit() {
    }

    onChooseFile() {
        $('#inputFile').click();
    }
    fileChangeListener($event) {
        let image: any = new Image();
        let file: File = $event.target.files[0];

        if (!file) {
            return;
        }

        let filename = file.name;
        let extension = filename.substring(filename.lastIndexOf('.') + 1).toLowerCase();

        if (extension == 'gif' || extension == 'png' || extension == 'bmp' || extension == 'jpeg' || extension == 'jpg') {
            // let reader = new FileReader();
            //
            // reader.onload = function(e) {
            //     $('#card-preview').attr('src', e.target.result);
            // }
            //
            // reader.readAsDataURL(file);

            this.settings.loading = true;
            this.api.uploadFile(file).subscribe(res => {
                this.settings.loading = false;
                if (res.success) {
                    this.profile.idcard_url = res.url;
                    this.profile.attach_name = file.name;
                }
            }, err => {
                this.settings.loading = false;
            });
        } else {
            this.notify.showNotification('Warning', 'Please select image file', 'warning');
        }
    }

    saveProfile() {
        if (!this.profile.firstname || this.profile.firstname == '') {
            this.notify.showNotification('Warning', 'Please enter first name.', 'warning');
            return;
        }
        if (!this.profile.surname || this.profile.surname == '') {
            this.notify.showNotification('Warning', 'Please enter surname.', 'warning');
            return;
        }
        if (!this.profile.phone_number || this.profile.phone_number == '') {
            this.notify.showNotification('Warning', 'Please enter phone number.', 'warning');
            return;
        }
        if (!this.profile.address || this.profile.address == '') {
            this.notify.showNotification('Warning', 'Please enter address.', 'warning');
            return;
        }
        if (!this.profile.verify_type || this.profile.verify_type == 0) {
            this.notify.showNotification('Warning', 'Please choose Verify Type and upload photo.', 'warning');
            return;
        }
        if (!this.profile.idcard_url || this.profile.idcard_url == '') {
            this.notify.showNotification('Warning', 'Please upload photo.', 'warning');
            return;
        }
        this.settings.loading = true;
        this.api.saveProfile({
            id: this.profile.id,
            firstname: this.profile.firstname,
            surname: this.profile.surname,
            phone_number: this.profile.phone_number,
            address: this.profile.address,
            attach_name: this.profile.attach_name,
            verify_type: this.profile.verify_type,
            idcard_url: this.profile.idcard_url,
            idcard_number: this.profile.idcard_number,
            verify_status: 1,
            note: ''
        }).subscribe(res => {
            this.settings.loading = false;
            if (res.success) {
                this.profile.verify_status = 1;
                this.notify.showNotification('Success', 'Profile was updated successfully. We will check your profile as soon as possible.', 'success');
            } else {
                this.notify.showNotification('Warning', 'Please try again.', 'warning');
            }
        }, err => {
            this.settings.loading = false;
        });
    }


}