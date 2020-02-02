import {Injectable} from '@angular/core';
import {Router, Resolve, RouterStateSnapshot, ActivatedRouteSnapshot} from '@angular/router';
import {Api} from "../services/api.service";

@Injectable()
export class ResetPasswordResolver implements Resolve<any> {

    constructor(private api: Api,
                private router: Router) {
    }

    resolve(route: ActivatedRouteSnapshot, state: RouterStateSnapshot): Promise<any> {
        let confirm_code = route.params['code'];

        return new Promise((resolve, reject) => {
            this.api.confirmResetCode({code: confirm_code}).subscribe(res => {
                if (res.success) {
                    resolve(res.user_id);
                } else {
                    this.router.navigate(['login']);
                    resolve(null);
                }
            }, err => {
                this.router.navigate(['login']);
                resolve(null);
            });
        });
    }
}