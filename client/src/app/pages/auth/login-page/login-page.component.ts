import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { lastValueFrom, map } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';

const Logger = new LoggerClass('Authentication');

@Component({
  selector: 'app-login-page',
  templateUrl: './login-page.component.html',
  styleUrls: ['./login-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LoginPageComponent {
  isLoading = false;
  error: string | null = null;

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
  }

  async doLogin() {
    this.isLoading = true;
    this.error = null;

    this.loginForm.disable();

    try {
      const loginResponse = await lastValueFrom(
        this.api.auth.login(this.loginForm.value.email, this.loginForm.value.password)
      );

      const loginData = loginResponse.body!.data;

      const redirectUrl: string | undefined = this.activatedRoute.snapshot.queryParams['redirect_url'];

      this.auth.login(loginData.user, loginData.access_token, loginData.refresh_token, redirectUrl);
    } catch (e) {
      if (e instanceof HttpErrorResponse) {
        this.error = ApiService.getErrorMessage(e);

        this.loginForm.get('password')?.reset();
      } else {
        this.error = 'An error occurred.';

        Logger.error('Error on login:', e);
      }

      this.isLoading = false;

      this.loginForm.enable();
    }
  }
}

