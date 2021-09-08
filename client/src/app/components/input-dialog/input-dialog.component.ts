import { Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
  selector: 'cb-input-dialog',
  templateUrl: './input-dialog.component.html',
  styleUrls: ['./input-dialog.component.scss'],
})
export class InputDialogComponent {
  value = '';

  constructor(
    @Inject(MAT_DIALOG_DATA)
    public data: {
      translate?: boolean;

      title: string;
      type?: string;
      label?: string;
      default?: string;
      btnConfirm?: string;
      btnDecline?: string;
    },
    private dialogRef: MatDialogRef<InputDialogComponent>
  ) {
    if (data.default != null) {
      this.value = data.default;
    }
  }

  get isTranslated() {
    return this.data.translate;
  }

  get btnConfirm() {
    return this.data.btnConfirm ? this.data.btnConfirm : 'Save';
  }

  get btnDecline() {
    return this.data.btnDecline ? this.data.btnDecline : 'Cancel';
  }

  confirm() {
    this.dialogRef.close(this.value);
  }
}
