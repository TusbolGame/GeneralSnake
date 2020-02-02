"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var core_1 = require("@angular/core");
var animations_1 = require("@angular/animations");
var ModalDialogComponent = (function () {
    function ModalDialogComponent() {
        this.closable = true;
        this.visibleChange = new core_1.EventEmitter();
    }
    ModalDialogComponent.prototype.ngOnInit = function () { };
    ModalDialogComponent.prototype.close = function () {
        this.visible = false;
        this.visibleChange.emit(this.visible);
    };
    return ModalDialogComponent;
}());
__decorate([
    core_1.Input()
], ModalDialogComponent.prototype, "closable", void 0);
__decorate([
    core_1.Input()
], ModalDialogComponent.prototype, "visible", void 0);
__decorate([
    core_1.Output()
], ModalDialogComponent.prototype, "visibleChange", void 0);
ModalDialogComponent = __decorate([
    core_1.Component({
        selector: 'modal-dialog',
        templateUrl: './modal.dialog.component.html',
        styleUrls: ['./modal.dialog.component.scss'],
        animations: [
            animations_1.trigger('dialog', [
                animations_1.transition('void => *', [
                    animations_1.style({ transform: 'scale3d(.3, .3, .3)' }),
                    animations_1.animate(100)
                ]),
                animations_1.transition('* => void', [
                    animations_1.animate(100, animations_1.style({ transform: 'scale3d(.0, .0, .0)' }))
                ])
            ])
        ]
    })
], ModalDialogComponent);
exports.ModalDialogComponent = ModalDialogComponent;
