import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { FormBuilder, FormGroup } from '@angular/forms';
import { lastValueFrom } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';

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

  constructor(private auth: AuthService, private api: ApiService, private fb: FormBuilder) {
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

      this.auth.login(loginData.user, loginData.access_token, loginData.refresh_token);
    } catch (e) {
      if (e instanceof HttpErrorResponse) {
        this.error = ApiService.getErrorMessage(e);

        this.loginForm.get('password')?.reset();
      } else {
        this.error = 'An error occurred.';

        console.error('Error on login:', e);
      }

      this.isLoading = false;

      this.loginForm.enable();
    }
  }
}

