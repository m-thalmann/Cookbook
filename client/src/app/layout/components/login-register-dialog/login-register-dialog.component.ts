import { Component } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialogRef } from '@angular/material/dialog';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/core/api/api.service';
import { User } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { UserService } from 'src/app/core/auth/user.service';
import { getFormError } from 'src/app/core/forms/Validation';

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

  constructor(
    private dialogRef: MatDialogRef<LoginRegisterDialogComponent>,
    private router: Router,
    private fb: FormBuilder,
    private api: ApiService,
    private user: UserService
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
        () => {
          if (!this.isLogin && this.password?.value != this.passwordConfirm?.value) {
            return { passwordsMismatch: true };
          }
          return null;
        },
      ],
      remember: [false],
    });
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
    let err = getFormError(this.loginForm.get(key));

    return err;
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

    let res: ApiResponse<{ user: User; token: string; info: string }>;

    if (this.isLogin) {
      res = await this.api.loginUser(this.email?.value, this.password?.value);
    } else {
      res = await this.api.registerUser(this.email?.value, this.password?.value, this.name?.value);
    }

    this.loading = false;
    this.loginForm.enable();

    try {
      if (res.isOK() && res.value !== null) {
        try {
          UserService.parseUserFromToken(res.value.token);
        } catch (e) {
          console.error(e);
          throw new Error();
        }

        this.user.login(res.value.token, this.remember?.value);

        await this.router.navigateByUrl('/home');
        location.reload();
      } else if (res.isNotFound()) {
        throw new Error('Username or password wrong!');
      } else if (res.isConflict()) {
        throw new Error('This email is already taken!');
      } else {
        let err = res.error || undefined;
        throw new Error(typeof err === 'object' ? err.info : err);
      }
    } catch (e) {
      this.error = e.message || 'An error occured!';
    }

    this.dialogRef.disableClose = false;
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
  }
}
