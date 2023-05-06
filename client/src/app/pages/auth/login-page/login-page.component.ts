import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, Subscription, distinctUntilChanged, lastValueFrom, take } from 'rxjs';
import { PromptDialogComponent } from 'src/app/components/dialogs/prompt-dialog/prompt-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Authentication');

@Component({
  selector: 'app-login-page',
  templateUrl: './login-page.component.html',
  styleUrls: ['./login-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    ReactiveFormsModule,
    TranslocoModule,
    MatFormFieldModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatInputModule,
  ],
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
    private activatedRoute: ActivatedRoute,
    private dialog: MatDialog,
    private snackbar: SnackbarService,
    private transloco: TranslocoService
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

      this.auth.setEmailVerified(loginResponse);
    } catch (e) {
      this.isLoading$.next(false);

      let errorMessage: string | null = this.transloco.translate('messages.errors.errorOccurred');

      if (e instanceof HttpErrorResponse) {
        this.loginForm.get('password')?.setValue('');

        if (ServerValidationHelper.setValidationErrors(e, this.loginForm)) {
          errorMessage = null;
        } else {
          errorMessage = this.api.getErrorMessage(e);
        }
      } else {
        Logger.error('Error on login:', e);
      }

      if (errorMessage) {
        this.error$.next(errorMessage);
      }
    }
  }

  async openResetPasswordDialog() {
    const email: string | null = await lastValueFrom(
      this.dialog
        .open(PromptDialogComponent, {
          width: '400px',
          data: {
            title: this.transloco.translate('auth.sendResetEmail'),
            label: this.transloco.translate('general.yourEmail'),
            type: 'email',
            btnConfirm: this.transloco.translate('actions.send'),
          },
        })
        .afterClosed()
        .pipe(take(1))
    );

    if (!email) {
      return;
    }

    this.isLoading$.next(true);

    try {
      await lastValueFrom(this.api.auth.sendResetPasswordEmail(email));

      this.snackbar.info('messages.resetEmailSent', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        defaultMessage: 'messages.errors.sendingResetEmail',
        translateMessage: true,
      }).message;

      Logger.error('Error sending reset email:', errorMessage, e);
    }

    this.isLoading$.next(false);
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
