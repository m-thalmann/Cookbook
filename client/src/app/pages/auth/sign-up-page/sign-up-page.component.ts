import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { AbstractControl, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { BehaviorSubject, distinctUntilChanged, lastValueFrom, Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';

const Logger = new LoggerClass('Authentication');

@Component({
  selector: 'app-sign-up-page',
  templateUrl: './sign-up-page.component.html',
  styleUrls: ['./sign-up-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SignUpPageComponent {
  private subSink = new Subscription();

  error$ = new BehaviorSubject<string | null>(null);
  isLoading$ = new BehaviorSubject<boolean>(false);

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

    this.subSink.add(
      this.isLoading$.pipe(distinctUntilChanged()).subscribe((isLoading) => {
        if (isLoading) {
          this.signUpForm.disable();
        } else {
          this.signUpForm.enable();
        }
      })
    );
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
    this.isLoading$.next(true);
    this.error$.next(null);

    try {
      const signUpResponse = await lastValueFrom(this.api.auth.signUp(this.signUpForm.value));

      const signUpData = signUpResponse.body!.data;

      const redirectUrl: string | undefined = this.activatedRoute.snapshot.queryParams['redirect-url'];

      this.auth.login(signUpData.user, signUpData.access_token, signUpData.refresh_token, redirectUrl);
    } catch (e) {
      this.isLoading$.next(false);

      let errorMessage: string | null = 'An error occurred.';

      if (e instanceof HttpErrorResponse) {
        if (ServerValidationHelper.setValidationErrors(e, this.signUpForm)) {
          errorMessage = null;
        } else {
          errorMessage = ApiService.getErrorMessage(e);
        }
      } else {
        Logger.error('Error on sign up:', e);
      }

      if (errorMessage) {
        this.error$.next(errorMessage);
      }
    }
  }
}
