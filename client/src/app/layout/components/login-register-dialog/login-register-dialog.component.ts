import { ChangeDetectorRef, Component } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { UserService } from 'src/app/core/auth/user.service';
import { ConfigService } from 'src/app/core/config/config.service';
import { getFormError } from 'src/app/core/forms/Validation';
import { Logger, LoggerColor } from 'src/app/core/functions';
import { TranslationService } from 'src/app/core/i18n/translation.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { ResetPasswordDialogComponent } from './components/reset-password-dialog/reset-password-dialog.component';
import { VerifyEmailDialogComponent } from './components/verify-email-dialog/verify-email-dialog.component';

@Component({
  selector: 'cb-login-register-dialog',
  templateUrl: './login-register-dialog.component.html',
  styleUrls: ['./login-register-dialog.component.scss'],
})
export class LoginRegisterDialogComponent {
  isLogin = true;

  loading = false;
  error: string | null = null;

  loginForm: FormGroup;

  private hcaptchaToken: string | undefined = undefined;
  private readonly hcaptchaEnabled: boolean;

  loginNeedsHcaptcha = false;

  constructor(
    private dialogRef: MatDialogRef<LoginRegisterDialogComponent>,
    private fb: FormBuilder,
    private api: ApiService,
    private user: UserService,
    private config: ConfigService,
    private changeDetectionRef: ChangeDetectorRef,
    private dialog: MatDialog,
    private snackbar: SnackbarService,
    private translation: TranslationService
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email, Validators.maxLength(100)]],
      name: [
        '',
        (control: AbstractControl) => {
          if (!this.isLogin) {
            let error = Validators.required(control);

            if (error) {
              return error;
            }

            return Validators.maxLength(20)(control);
          }
          return null;
        },
      ],
      password: ['', Validators.required],
      passwordConfirm: [
        '',
        (control: AbstractControl) => {
          if (!this.isLogin) {
            if (this.password?.value != this.passwordConfirm?.value) {
              return { passwordsMismatch: true };
            }
            return Validators.required(control);
          }
          return null;
        },
      ],
      remember: [false],
    });

    this.hcaptchaEnabled = this.config.get('hcaptcha.enabled', false);
  }

  get email() {
    return this.loginForm.get('email');
  }
  get name() {
    return this.loginForm.get('name');
  }
  get password() {
    return this.loginForm.get('password');
  }
  get passwordConfirm() {
    return this.loginForm.get('passwordConfirm');
  }
  get remember() {
    return this.loginForm.get('remember');
  }

  getFormError(key: string) {
    return getFormError(this.loginForm.get(key));
  }

  get showHCaptcha() {
    return (!this.isLogin || this.loginNeedsHcaptcha) && this.hcaptchaEnabled;
  }

  get isHCaptchaValid() {
    return !this.hcaptchaEnabled || this.hcaptchaToken !== undefined || !this.isLogin || !this.loginNeedsHcaptcha;
  }

  /**
   * Login/Register
   */
  async action() {
    if (this.loginForm.invalid) return;

    this.error = null;
    this.loading = true;
    this.loginForm.disable();
    this.dialogRef.disableClose = true;

    let res: ApiResponse<any>;

    if (this.isLogin) {
      res = await this.api.loginUser(
        this.email?.value,
        this.password?.value,
        this.loginNeedsHcaptcha ? this.hcaptchaToken : undefined
      );
    } else {
      res = await this.api.registerUser(
        this.email?.value,
        this.password?.value,
        this.name?.value,
        this.translation.language || 'en',
        this.hcaptchaToken
      );
    }

    this.loading = false;
    this.loginForm.enable();

    try {
      if (this.isLogin) {
        if (res.isOK() && res.value !== null) {
          if (res.value.token) {
            this.user.login(res.value.token, res.value.user, this.remember?.value);
          }

          this.dialogRef.close();
          this.snackbar.info('messages.users.login_successful');
        } else if (res.isNotFound()) {
          this.error = 'messages.users.credentials_wrong';
        } else if (res.isForbidden()) {
          if (res.error?.errorKey === 'forbidden.email_not_verified') {
            // email not verified

            let verified = await this.dialog
              .open(VerifyEmailDialogComponent, {
                data: this.email?.value,
              })
              .afterClosed()
              .toPromise();

            if (verified) {
              await this.action();
              return;
            }
          } else if (res.error?.errorKey === 'forbidden.too_many_bad_logins') {
            // too many bad logins

            this.loginNeedsHcaptcha = true;
            this.error = 'messages.users.credentials_wrong';
          } else {
            throw new Error(res.error?.errorKey ? `api_error.${res.error.errorKey}` : undefined);
          }
        } else {
          throw new Error(res.error?.errorKey ? `api_error.${res.error.errorKey}` : undefined);
        }
      } else {
        if (res.isOK() && res.value !== null) {
          // Auto-login after register

          this.isLogin = true;

          await this.action();
          return;
        } else if (res.isConflict()) {
          this.error = 'messages.users.email_already_taken';
        } else {
          throw new Error(res.error?.errorKey ? `api_error.${res.error.errorKey}` : undefined);
        }
      }
    } catch (e: any) {
      this.error = e.message || 'messages.error_occurred';
      Logger.error('Login/Register', LoggerColor.red, 'Error on login/register:', res.error);
    }
  }

  /**
   * Show the ResetPassword-Dialog
   */
  showResetPasswordDialog() {
    this.dialog.open(ResetPasswordDialogComponent);
  }

  /**
   * Switches between login/register and updates the validity of the form
   */
  toggleLogin() {
    this.isLogin = !this.isLogin;

    this.email?.updateValueAndValidity();
    this.name?.updateValueAndValidity();
    this.password?.updateValueAndValidity();
    this.passwordConfirm?.updateValueAndValidity();
    this.remember?.updateValueAndValidity();

    this.error = null;
    this.loginNeedsHcaptcha = false;
  }

  onCaptchaVerified(token: string) {
    this.hcaptchaToken = token;
    this.changeDetectionRef.detectChanges();
  }
}
