import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { BehaviorSubject, lastValueFrom, take } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from '../../../core/helpers/logger';
import { SnackbarService } from '../../../core/services/snackbar.service';
import { SettingsSectionComponent } from '../components/settings-section/settings-section.component';
import { AccountSettingsUpdatePasswordComponent } from './components/account-settings-update-password/account-settings-update-password.component';
import { AccountSettingsUpdateProfileComponent } from './components/account-settings-update-profile/account-settings-update-profile.component';

const Logger = new LoggerClass('Settings');

@Component({
  selector: 'app-account-settings-page',
  templateUrl: './account-settings-page.component.html',
  styleUrls: ['./account-settings-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    SettingsSectionComponent,
    AccountSettingsUpdateProfileComponent,
    AccountSettingsUpdatePasswordComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountSettingsPageComponent {
  isLoading$ = new BehaviorSubject<boolean>(false);

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private dialog: MatDialog,
    private snackbar: SnackbarService
  ) {}

  async deleteAccount() {
    if (this.isLoading$.value) return;

    const user = await this.getUser();

    const confirmed = await lastValueFrom(
      this.dialog
        .open(ConfirmDialogComponent, {
          data: {
            title: 'Are you sure?',
            content: 'This action cannot be undone.',
            btnConfirm: 'Delete',
            btnDecline: 'Abort',
            warn: true,
          },
        })
        .afterClosed()
        .pipe(take(1))
    );

    if (!confirmed) {
      return;
    }

    this.isLoading$.next(true);

    try {
      await lastValueFrom(this.api.users.delete(user.id).pipe(take(1)));

      await this.auth.initialize();

      this.snackbar.info({ message: 'Account deleted successfully' });

      this.isLoading$.next(false);
    } catch (e) {
      const errorMessage = ApiService.getErrorMessage(e);

      this.snackbar.warn({ message: 'Error deleting account' });
      this.isLoading$.next(false);
      Logger.error('Error deleting account:', errorMessage);
    }
  }

  private async getUser() {
    return (await lastValueFrom(this.auth.user$.pipe(take(1))))!;
  }
}
