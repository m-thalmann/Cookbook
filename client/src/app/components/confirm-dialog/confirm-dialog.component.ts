import { Component, Inject } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
  selector: 'cb-confirm-dialog',
  templateUrl: './confirm-dialog.component.html',
  styleUrls: ['./confirm-dialog.component.scss'],
})
export class ConfirmDialogComponent {
  constructor(
    @Inject(MAT_DIALOG_DATA)
    public data: {
      translate?: boolean;

      title?: string;
      content?: string;
      translationKey?: string;
      titleReplace?: { [key: string]: string };
      contentReplace?: { [key: string]: string };
      btnConfirm?: string;
      btnDecline?: string;
      warn?: boolean;
      onlyOk?: boolean;
      error?: boolean;
    }
  ) {}

  get isTranslated() {
    return this.data.translate;
  }

  get translateTitle() {
    const key = this.data.translationKey ? this.data.translationKey + '.title' : this.data.title || '';

    return { key: key, replace: this.data.titleReplace };
  }

  get translateContent() {
    const key = this.data.translationKey ? this.data.translationKey + '.content' : this.data.content || '';

    return { key: key, replace: this.data.contentReplace };
  }

  get btnConfirm() {
    return this.data.btnConfirm ? this.data.btnConfirm : 'OK';
  }

  get btnDecline() {
    return this.data.btnDecline ? this.data.btnDecline : 'Cancel';
  }
}
