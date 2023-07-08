import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { SettingsSectionComponent } from '../../../components/settings-section/settings-section.component';
import { Logger as LoggerClass } from '../../../core/helpers/logger';
import { SnackbarService } from '../../../core/services/snackbar.service';
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
    TranslocoModule,
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
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {}

  async deleteAccount() {
    if (this.isLoading$.value) return;

    const user = await this.getUser();

    const confirmed = await toPromise(
      this.dialog
        .open(ConfirmDialogComponent, {
          data: {
            title: this.transloco.translate('messages.areYouSure'),
            content: this.transloco.translate('messages.thisActionCantBeUndone'),
            btnConfirm: this.transloco.translate('actions.delete'),
            btnDecline: this.transloco.translate('actions.abort'),
            warn: true,
          },
        })
        .afterClosed()
    );

    if (!confirmed) {
      return;
    }

    this.isLoading$.next(true);

    try {
      await toPromise(this.api.users.delete(user.id));

      await this.auth.initialize();

      this.snackbar.info('messages.accountDeleted', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        defaultMessage: 'messages.errors.deletingAccount',
        translateMessage: true,
      }).message;

      Logger.error('Error deleting account:', errorMessage, e);
    }

    this.isLoading$.next(false);
  }

  private async getUser() {
    return (await toPromise(this.auth.user$))!;
  }
}
