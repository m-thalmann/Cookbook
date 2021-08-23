import { ChangeDetectorRef, Component } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { ApiService } from 'src/app/core/api/api.service';
import { User } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { UserService } from 'src/app/core/auth/user.service';
import { ConfigService } from 'src/app/core/config/config.service';
import { getFormError } from 'src/app/core/forms/Validation';
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

  private hcaptchaToken: string | null = null;
  private readonly hcaptchaEnabled: boolean;

  constructor(
    private dialogRef: MatDialogRef<LoginRegisterDialogComponent>,
    private fb: FormBuilder,
    private api: ApiService,
    private user: UserService,
    private config: ConfigService,
    private changeDetectionRef: ChangeDetectorRef,
    private dialog: MatDialog
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
    return !this.isLogin && this.hcaptchaEnabled;
  }

  get isHCaptchaValid() {
    return this.isLogin || !this.hcaptchaEnabled || this.hcaptchaToken !== null;
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

    let res: ApiResponse<{ user: User; token?: string; info: string }>;

    if (this.isLogin) {
      res = await this.api.loginUser(this.email?.value, this.password?.value);
    } else {
      res = await this.api.registerUser(this.email?.value, this.password?.value, this.name?.value, this.hcaptchaToken);
    }

    this.loading = false;
    this.loginForm.enable();

    try {
      if (res.isOK() && res.value !== null) {
        if (this.isLogin && res.value.token) {
          try {
            UserService.parseUserFromToken(res.value.token);
          } catch (e) {
            console.error(e);
            throw new Error();
          }

          this.user.login(res.value.token, this.remember?.value);

          location.href = location.pathname;
        } else {
          this.isLogin = true;

          await this.action();
          return;
        }
      } else if (res.isNotFound()) {
        throw new Error('Username or password wrong!');
      } else if (res.isConflict()) {
        throw new Error('This email is already taken!');
      } else if (res.isForbidden() && this.isLogin) {
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
      } else {
        let err = res.error || undefined;
        throw new Error(typeof err === 'object' ? err.info : err);
      }
    } catch (e) {
      this.error = e.message || 'An error occurred!';
      console.error('Error on login/register:', res.error);
    }

    this.dialogRef.disableClose = false;
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
  }

  onCaptchaVerified(token: string) {
    this.hcaptchaToken = token;
    this.changeDetectionRef.detectChanges();
  }
}
