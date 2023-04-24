import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { TranslocoService } from '@ngneat/transloco';

@Component({
  selector: 'app-prompt-dialog',
  templateUrl: './prompt-dialog.component.html',
  styleUrls: ['./prompt-dialog.component.scss'],
  standalone: true,
  imports: [CommonModule, MatDialogModule, MatButtonModule, FormsModule, MatFormFieldModule, MatInputModule],
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
    private dialogRef: MatDialogRef<PromptDialogComponent>,
    private translocoService: TranslocoService
  ) {
    if (data.default != null) {
      this.value = data.default;
    }
  }

  get btnConfirm() {
    return this.data.btnConfirm ? this.data.btnConfirm : this.translocoService.translate('actions.confirm');
  }

  get btnDecline() {
    return this.data.btnDecline ? this.data.btnDecline : this.translocoService.translate('actions.cancel');
  }

  confirm() {
    this.dialogRef.close(this.value || null);
  }
}
