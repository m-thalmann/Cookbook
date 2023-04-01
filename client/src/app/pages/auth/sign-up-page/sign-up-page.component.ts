import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, ChangeDetectorRef, Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { BehaviorSubject, Observable, Subscription, distinctUntilChanged, lastValueFrom, map, startWith } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { CustomValidators } from 'src/app/core/forms/CustomValidators';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { ConfigService } from 'src/app/core/services/config.service';

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

  formValid$: Observable<boolean>;

  constructor(
    private auth: AuthService,
    private config: ConfigService,
    private api: ApiService,
    private fb: FormBuilder,
    private activatedRoute: ActivatedRoute,
    private changeDetector: ChangeDetectorRef
  ) {
    this.signUpForm = this.fb.group(
      {
        name: ['', [Validators.required]],
        email: ['', [Validators.required, Validators.email]],
        password: ['', [Validators.required]],
        password_confirmation: ['', [Validators.required]],
      },
      { validators: CustomValidators.checkPasswords('password', 'password_confirmation') }
    );

    this.formValid$ = this.signUpForm.statusChanges.pipe(
      map((status) => status === 'VALID'),
      startWith(this.signUpForm.valid),
      distinctUntilChanged()
    );

    this.subSink.add(
      this.isLoading$.pipe(distinctUntilChanged()).subscribe((isLoading) => {
        if (isLoading) {
          this.signUpForm.disable();
        } else {
          this.signUpForm.enable();
        }
      })
    );

    if (this.config.get('hcaptcha.enabled', false)) {
      this.signUpForm.addControl('hcaptcha_token', this.fb.control('', [Validators.required]));
    }
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

  get hcaptchaEnabled() {
    return this.signUpForm.get('hcaptcha_token') !== null;
  }

  onCaptchaVerified(token: string) {
    this.signUpForm.get('hcaptcha_token')?.setValue(token);
    // the formValid$ observable somehow does not trigger change detection
    this.changeDetector.detectChanges();
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
