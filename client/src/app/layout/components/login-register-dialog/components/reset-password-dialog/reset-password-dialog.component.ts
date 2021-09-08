import { Component, ViewChild } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialogRef } from '@angular/material/dialog';
import { MatStepper } from '@angular/material/stepper';
import { ApiService } from 'src/app/core/api/api.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'cb-reset-password-dialog',
  templateUrl: './reset-password-dialog.component.html',
  styleUrls: ['./reset-password-dialog.component.scss'],
})
export class ResetPasswordDialogComponent {
  @ViewChild('stepper') stepper!: MatStepper;

  emailForm: FormGroup;
  resetForm: FormGroup;

  emailSent = false;

  loading = false;
  error: string | null = null;

  constructor(
    private dialogRef: MatDialogRef<ResetPasswordDialogComponent>,
    private api: ApiService,
    private snackbar: SnackbarService,
    private fb: FormBuilder
  ) {
    this.emailForm = this.fb.group({
      email: ['', [Validators.email, Validators.required]],
    });

    this.resetForm = this.fb.group({
      token: ['', Validators.required],
      password: ['', Validators.required],
    });
  }

  get email() {
    return this.emailForm.get('email')?.value;
  }

  get token() {
    return this.resetForm.get('token')?.value;
  }
  get password() {
    return this.resetForm.get('password')?.value;
  }

  /**
   * Sends the reset email
   */
  async sendEmail() {
    this.loading = true;
    this.dialogRef.disableClose = true;
    this.emailForm.disable();

    this.emailSent = false;
    this.error = null;

    let res = await this.api.sendResetPasswordEmail(this.email);

    if (res.isOK()) {
      this.emailSent = true;

      setTimeout(() => {
        this.stepper.next();
      }, 0);
    } else {
      this.error = 'messages.email.error_sending_email';
      this.emailForm.enable();
    }

    this.loading = false;
    this.dialogRef.disableClose = false;
  }

  /**
   * Reset the password
   */
  async resetPassword() {
    this.loading = true;
    this.dialogRef.disableClose = true;
    this.resetForm.disable();

    this.error = null;

    let res = await this.api.resetPassword(this.email, this.token, this.password);

    if (res.isOK()) {
      this.snackbar.info('messages.users.password_reset_successful');

      this.dialogRef.close();
    } else if (res.isNotFound()) {
      this.error = 'messages.login_register.wrong_code_submitted';
    } else {
      this.error = 'messages.admin.error_saving_new_password';
    }

    this.loading = false;
    this.dialogRef.disableClose = false;
    this.resetForm.enable();
  }
}
