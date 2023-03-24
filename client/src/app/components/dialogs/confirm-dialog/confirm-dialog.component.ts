import { ChangeDetectionStrategy, Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
  selector: 'app-confirm-dialog',
  templateUrl: './confirm-dialog.component.html',
  styleUrls: ['./confirm-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ConfirmDialogComponent {
  constructor(
    @Inject(MAT_DIALOG_DATA)
    public data: {
      title?: string;
      content?: string;
      btnConfirm?: string;
      btnDecline?: string;
      warn?: boolean;
      onlyOk?: boolean;
      error?: boolean;
    }
  ) {}

  get btnConfirm() {
    return this.data.btnConfirm ? this.data.btnConfirm : 'OK';
  }

  get btnDecline() {
    return this.data.btnDecline ? this.data.btnDecline : 'Cancel';
  }
}

