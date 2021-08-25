import { Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from 'src/app/core/api/api.service';

@Component({
  selector: 'cb-verify-email-dialog',
  templateUrl: './verify-email-dialog.component.html',
  styleUrls: ['./verify-email-dialog.component.scss'],
})
export class VerifyEmailDialogComponent {
  private _code: string = '';

  loading = false;
  error: string | null = null;

  constructor(
    private dialogRef: MatDialogRef<VerifyEmailDialogComponent>,
    @Inject(MAT_DIALOG_DATA)
    public email: string,
    private api: ApiService,
    private snackBar: MatSnackBar
  ) {}

  set code(code: string) {
    this._code = code;

    if (code.length === 5) {
      this.verify();
    }
  }
  get code() {
    return this._code;
  }

  /**
   * Verify the email address using the supplied code
   */
  private async verify() {
    this.loading = true;
    this.error = null;
    this.dialogRef.disableClose = true;

    let res = await this.api.verifyEmail(this.email, this.code);

    if (res.isOK()) {
      this.dialogRef.close(true);
    } else {
      this.code = '';

      if (res.isForbidden()) {
        this.error = 'Verification code is wrong or has expired';
      } else {
        this.error = 'Error verifying email';
      }
    }

    this.loading = false;
    this.dialogRef.disableClose = false;
  }

  /**
   * Resend the verification email
   */
  async resend() {
    this.loading = true;
    this.dialogRef.disableClose = true;

    let res = await this.api.resendVerificationEmail(this.email);

    if (res.isOK()) {
      this.snackBar.open('Verification-email sent!', 'OK', {
        duration: 5000,
      });
    } else {
      this.snackBar.open('Error sending email', 'OK', {
        duration: 10000,
        panelClass: 'action-warn',
      });
    }

    this.loading = false;
    this.dialogRef.disableClose = false;
  }
}
