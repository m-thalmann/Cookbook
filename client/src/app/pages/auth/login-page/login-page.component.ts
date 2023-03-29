import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { BehaviorSubject, distinctUntilChanged, lastValueFrom, Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';

const Logger = new LoggerClass('Authentication');

@Component({
  selector: 'app-login-page',
  templateUrl: './login-page.component.html',
  styleUrls: ['./login-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginPageComponent implements OnDestroy {
  private subSink = new Subscription();

  error$ = new BehaviorSubject<string | null>(null);
  isLoading$ = new BehaviorSubject<boolean>(false);

  loginForm: FormGroup;

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private fb: FormBuilder,
    private activatedRoute: ActivatedRoute
  ) {
    this.loginForm = this.fb.group({
      email: [''],
      password: [''],
    });

    this.subSink.add(
      this.isLoading$.pipe(distinctUntilChanged()).subscribe((isLoading) => {
        if (isLoading) {
          this.loginForm.disable();
        } else {
          this.loginForm.enable();
        }
      })
    );
  }

  async doLogin() {
    this.isLoading$.next(true);
    this.error$.next(null);

    try {
      const loginResponse = await lastValueFrom(
        this.api.auth.login(this.loginForm.value.email, this.loginForm.value.password)
      );

      const loginData = loginResponse.body!.data;

      const redirectUrl: string | undefined = this.activatedRoute.snapshot.queryParams['redirect-url'];

      this.auth.login(loginData.user, loginData.access_token, loginData.refresh_token, redirectUrl);
    } catch (e) {
      this.isLoading$.next(false);

      let errorMessage: string | null = 'An error occurred.';

      if (e instanceof HttpErrorResponse) {
        this.loginForm.get('password')?.setValue('');

        if (ServerValidationHelper.setValidationErrors(e, this.loginForm)) {
          errorMessage = null;
        } else {
          errorMessage = ApiService.getErrorMessage(e);
        }
      } else {
        Logger.error('Error on login:', e);
      }

      if (errorMessage) {
        this.error$.next(errorMessage);
      }
    }
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
