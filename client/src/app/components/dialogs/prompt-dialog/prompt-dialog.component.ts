import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Inject } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatDialogRef, MAT_DIALOG_DATA, MatDialogModule } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';

@Component({
  selector: 'app-prompt-dialog',
  templateUrl: './prompt-dialog.component.html',
  styleUrls: ['./prompt-dialog.component.scss'],
  standalone: true,
  imports: [CommonModule, MatDialogModule, FormsModule, MatFormFieldModule, MatInputModule],
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
