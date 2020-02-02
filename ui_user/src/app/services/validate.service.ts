/**
 * Created by ApolloYr on 11/17/2017.
 */

import {Injectable} from '@angular/core';
import {FormBuilder, FormGroup, Validators, FormControl, AbstractControl} from '@angular/forms';

@Injectable()
export class Validate {
    constructor() {
    }

    isFieldValid(form: FormGroup, field: string) {
        return !form.get(field).valid && form.get(field).touched;
    }

    displayFieldCss(form: FormGroup, field: string) {
        return {
            'has-error': this.isFieldValid(form, field),
            'has-feedback': this.isFieldValid(form, field)
        };
    }

    validateAllFormFields(formGroup: FormGroup) {
        Object.keys(formGroup.controls).forEach(field => {
            const control = formGroup.get(field);
            if (control instanceof FormControl) {
                control.markAsTouched({onlySelf: true});
            } else if (control instanceof FormGroup) {
                this.validateAllFormFields(control);
            }
        });
    }

    round(value, place) {
        let val = Math.round(value * Math.pow(10, place)) / Math.pow(10, place);
        return val.toFixed(place);
    }

    floor(value, place) {
        let val = Math.floor(value * Math.pow(10, place)) / Math.pow(10, place);
        return val.toFixed(place);
    }

    isNullOrEmpty(value) {
        if (value == null) return true;
        if (value == '') return true;
        return false;
    }
}

