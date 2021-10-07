import { Component, Inject } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { ApiService } from 'src/app/core/api/api.service';
import { Logger, LoggerColor } from 'src/app/core/functions';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

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
    private snackbar: SnackbarService
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
        this.error = 'messages.login_register.wrong_verification_code';
      } else {
        this.error = 'messages.login_register.error_verifying_email';

        Logger.error('VerifyEmail', LoggerColor.red, 'Error verifying email:', res.error);
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
      this.snackbar.info('messages.email.verification_email_sent');
    } else {
      if (res.status === 405 && res.error) {
        this.snackbar.warn(`api_error.${res.error.errorKey}`);
      } else {
        this.snackbar.warn('messages.email.error_sending_email');
      }

      Logger.error('VerifyEmail', LoggerColor.red, 'Error sending email:', res.error);
    }

    this.loading = false;
    this.dialogRef.disableClose = false;
  }
}
