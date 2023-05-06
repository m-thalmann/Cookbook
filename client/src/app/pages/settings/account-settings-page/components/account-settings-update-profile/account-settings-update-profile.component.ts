import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, Subscription, distinctUntilChanged, lastValueFrom, take } from 'rxjs';
import { PromptDialogComponent } from 'src/app/components/dialogs/prompt-dialog/prompt-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { Logger as LoggerClass } from '../../../../../core/helpers/logger';
import { SettingsSectionComponent } from '../../../components/settings-section/settings-section.component';

const Logger = new LoggerClass('Settings');

@Component({
  selector: 'app-account-settings-update-profile',
  templateUrl: './account-settings-update-profile.component.html',
  styleUrls: ['./account-settings-update-profile.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    TranslocoModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    SettingsSectionComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountSettingsUpdateProfileComponent implements OnDestroy {
  private subSink = new Subscription();

  isLoading$ = new BehaviorSubject<boolean>(false);

  form = this.fb.group({
    name: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
    email: new FormControl<string>('', { nonNullable: true, validators: [Validators.required, Validators.email] }),
  });

  updateError$ = new BehaviorSubject<string | null>(null);

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private fb: FormBuilder,
    private dialog: MatDialog,
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {
    this.loadFormData();

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

  async loadFormData() {
    const user = await this.getUser();

    this.form.patchValue({
      name: user.name,
      email: user.email,
    });
  }

  async updateProfile() {
    if (this.isLoading$.value) return;

    this.updateError$.next(null);

    const user = await this.getUser();

    const hasChangedEmail = this.form.value.email !== user.email;

    const updateData: { name?: string; email?: string; current_password?: string } = {
      name: this.form.value.name,
      email: hasChangedEmail ? this.form.value.email : undefined,
    };

    if (hasChangedEmail) {
      const confirmedPassword: string | null = await lastValueFrom(
        this.dialog
          .open(PromptDialogComponent, {
            data: {
              title: this.transloco.translate('pages.settings.confirmCurrentPassword'),
              type: 'password',
            },
          })
          .afterClosed()
          .pipe(take(1))
      );

      if (!confirmedPassword) {
        return;
      }

      updateData['current_password'] = confirmedPassword;
    }

    this.isLoading$.next(true);

    try {
      await lastValueFrom(this.api.users.update(user.id, updateData).pipe(take(1)));

      await this.auth.initialize();

      this.snackbar.info('messages.profileUpdated', { translateMessage: true });

      this.isLoading$.next(false);
    } catch (e) {
      this.isLoading$.next(false);

      let errorMessage: string | null = this.transloco.translate('messages.errors.unexpectedError');

      if (e instanceof HttpErrorResponse) {
        if (ServerValidationHelper.setValidationErrors(e, this.form)) {
          errorMessage = null;
        } else {
          errorMessage = this.api.getErrorMessage(e);
        }
      }

      if (errorMessage) {
        this.updateError$.next(errorMessage);
      }

      Logger.error('Error updating profile:', errorMessage, e);
    }
  }

  private async getUser() {
    return (await lastValueFrom(this.auth.user$.pipe(take(1))))!;
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
