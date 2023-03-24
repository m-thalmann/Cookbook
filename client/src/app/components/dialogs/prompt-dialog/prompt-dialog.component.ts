import { ChangeDetectionStrategy, Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';

@Component({
  selector: 'app-prompt-dialog',
  templateUrl: './prompt-dialog.component.html',
  styleUrls: ['./prompt-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PromptDialogComponent {
  value: string | null = null;

  constructor(
    @Inject(MAT_DIALOG_DATA)
    public data: {
      title: string;
      type?: string;
      label?: string;
      default?: string;
      btnConfirm?: string;
      btnDecline?: string;
    },
    private dialogRef: MatDialogRef<PromptDialogComponent>
  ) {
    if (data.default != null) {
      this.value = data.default;
    }
  }

  get btnConfirm() {
    return this.data.btnConfirm ? this.data.btnConfirm : 'Save';
  }

  get btnDecline() {
    return this.data.btnDecline ? this.data.btnDecline : 'Cancel';
  }

  confirm() {
    this.dialogRef.close(this.value || null);
  }
}

