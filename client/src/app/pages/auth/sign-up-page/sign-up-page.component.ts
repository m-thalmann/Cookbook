import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { lastValueFrom } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';

const Logger = new LoggerClass('Authentication');

@Component({
  selector: 'app-sign-up-page',
  templateUrl: './sign-up-page.component.html',
  styleUrls: ['./sign-up-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SignUpPageComponent {
  isLoading = false;
  error: string | null = null;

  signUpForm: FormGroup;

  private hcaptchaToken?: string; // TODO:

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private fb: FormBuilder,
    private activatedRoute: ActivatedRoute
  ) {
    this.signUpForm = this.fb.group({
      name: ['', [Validators.required]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required]],
      password_confirmation: [
        '',
        [
          Validators.required,
          (_: AbstractControl) => {
            if (this.password?.value != this.passwordConfirmation?.value) {
              return { passwordsMismatch: true };
            }
            return null;
          },
        ],
      ],
    });
  }

  get name() {
    return this.signUpForm?.get('name');
  }
  get email() {
    return this.signUpForm?.get('email');
  }
  get password() {
    return this.signUpForm?.get('password');
  }
  get passwordConfirmation() {
    return this.signUpForm?.get('password_confirmation');
  }

  async doSignUp() {
    this.isLoading = true;
    this.error = null;

    this.signUpForm.disable();

    try {
      const signUpResponse = await lastValueFrom(this.api.auth.signUp(this.signUpForm.value));

      const signUpData = signUpResponse.body!.data;

      const redirectUrl: string | undefined = this.activatedRoute.snapshot.queryParams['redirect-url'];

      this.auth.login(signUpData.user, signUpData.access_token, signUpData.refresh_token, redirectUrl);
    } catch (e) {
      this.signUpForm.enable();

      if (e instanceof HttpErrorResponse) {
        if (e.status !== 422) {
          this.error = e.error.message;
        } else {
          const validationErrors = e.error.errors;

          Object.keys(validationErrors).forEach((controlName) => {
            const formControl = this.signUpForm.get(controlName);

            if (formControl) {
              formControl.setErrors({
                serverError: validationErrors[controlName][0],
              });
            }
          });
        }
      } else {
        this.error = 'An error occurred.';

        Logger.error('Error on sign up:', e);
      }

      this.isLoading = false;
    }
  }
}
