import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';
import { trigger, style, animate, transition } from '@angular/animations';

@Component({
    selector: 'modal-dialog',
    templateUrl: './modal.dialog.component.html',
    styleUrls: ['./modal.dialog.component.scss'],
    animations: [
        trigger('dialog', [
            transition('void => *', [
                style({ transform: 'scale3d(.3, .3, .3)' }),
                animate(100)
            ]),
            transition('* => void', [
                animate(100, style({ transform: 'scale3d(.0, .0, .0)' }))
            ])
        ])
    ]
})

export class ModalDialogComponent implements OnInit {
    @Input() closable = true;
    @Input() visible: boolean;

    @Output() visibleChange: EventEmitter<boolean> = new EventEmitter<boolean>();

    constructor() { }

    ngOnInit() { }

    close() {
        if (!this.closable) {
            return;
        }
        this.visible = false;
        this.visibleChange.emit(this.visible);
    }
}