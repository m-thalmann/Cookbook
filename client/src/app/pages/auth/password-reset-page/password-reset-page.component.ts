import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import {
  BehaviorSubject,
  Observable,
  Subscription,
  distinctUntilChanged,
  lastValueFrom,
  map,
  startWith,
  take,
} from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { CustomValidators } from 'src/app/core/forms/CustomValidators';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Authentication');

@Component({
  selector: 'app-password-reset-page',
  templateUrl: './password-reset-page.component.html',
  styleUrls: ['./password-reset-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
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
export class PasswordResetPageComponent implements OnDestroy {
  private subSink = new Subscription();

  error$ = new BehaviorSubject<string | null>(null);
  isLoading$ = new BehaviorSubject<boolean>(false);

  form: FormGroup;

  email = this.route.snapshot.params['email'];
  private token = this.route.snapshot.params['token'];

  formValid$: Observable<boolean>;

  constructor(
    private fb: FormBuilder,
    private route: ActivatedRoute,
    private router: Router,
    private api: ApiService,
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {
    this.form = this.fb.group(
      {
        password: ['', [Validators.required]],
        password_confirmation: ['', [Validators.required]],
      },
      { validators: CustomValidators.checkPasswords('password', 'password_confirmation') }
    );

    this.formValid$ = this.form.statusChanges.pipe(
      map((status) => status === 'VALID'),
      startWith(this.form.valid),
      distinctUntilChanged()
    );

    this.subSink.add(
      this.isLoading$.pipe(distinctUntilChanged()).subscribe((isLoading) => {
        if (isLoading) {
          this.form.disable();
        } else {
          this.form.enable();
        }
      })
    );
  }

  get password() {
    return this.form?.get('password');
  }
  get passwordConfirmation() {
    return this.form?.get('password_confirmation');
  }

  async doReset() {
    this.isLoading$.next(true);
    this.error$.next(null);

    try {
      await lastValueFrom(this.api.auth.resetPassword(this.token, this.email, this.password?.value).pipe(take(1)));

      this.snackbar.info('messages.passwordReset', { translateMessage: true });

      this.router.navigateByUrl('/login');
    } catch (e) {
      this.isLoading$.next(false);

      let errorMessage: string | null = this.transloco.translate('messages.errors.errorOccurred');

      if (e instanceof HttpErrorResponse) {
        if (ServerValidationHelper.setValidationErrors(e, this.form)) {
          errorMessage = null;
        } else {
          errorMessage = ApiService.getErrorMessage(e);
        }
      }

      Logger.error('Error on password reset:', errorMessage, e);

      if (errorMessage) {
        this.error$.next(errorMessage);
      }
    }
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
