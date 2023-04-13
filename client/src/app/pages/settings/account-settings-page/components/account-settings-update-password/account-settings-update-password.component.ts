import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { BehaviorSubject, Subscription, distinctUntilChanged, lastValueFrom, take } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { CustomValidators } from 'src/app/core/forms/CustomValidators';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { Logger as LoggerClass } from '../../../../../core/helpers/logger';
import { SettingsSectionComponent } from '../../../components/settings-section/settings-section.component';

const Logger = new LoggerClass('Settings');

@Component({
  selector: 'app-account-settings-update-password',
  templateUrl: './account-settings-update-password.component.html',
  styleUrls: ['./account-settings-update-password.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    SettingsSectionComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountSettingsUpdatePasswordComponent implements OnDestroy {
  private subSink = new Subscription();

  isLoading$ = new BehaviorSubject<boolean>(false);

  form = this.fb.group(
    {
      oldPassword: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
      newPassword: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
      confirmedPassword: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
    },
    { validators: CustomValidators.checkPasswords('newPassword', 'confirmedPassword') }
  );

  updateError$ = new BehaviorSubject<string | null>(null);

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private fb: FormBuilder,
    private snackbar: SnackbarService
  ) {
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

  async updatePassword() {
    if (this.isLoading$.value) return;

    this.updateError$.next(null);

    const user = await this.getUser();

    this.isLoading$.next(true);

    try {
      await lastValueFrom(
        this.api.users
          .update(user.id, {
            password: this.form.value.newPassword,
            current_password: this.form.value.oldPassword,
          })
          .pipe(take(1))
      );

      await this.auth.initialize();

      this.snackbar.info({ message: 'Password updated successfully' });

      this.isLoading$.next(false);
    } catch (e) {
      this.isLoading$.next(false);

      let errorMessage: string | null = 'An error occurred.';

      if (e instanceof HttpErrorResponse) {
        if (
          ServerValidationHelper.setValidationErrors(e, this.form, {
            current_password: 'oldPassword',
            password: 'newPassword',
          })
        ) {
          errorMessage = null;
        } else {
          errorMessage = ApiService.getErrorMessage(e);
        }
      }

      if (errorMessage) {
        this.updateError$.next(errorMessage);
      }

      Logger.error('Error updating password:', errorMessage, e);
    }
  }

  private async getUser() {
    return (await lastValueFrom(this.auth.user$.pipe(take(1))))!;
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}

