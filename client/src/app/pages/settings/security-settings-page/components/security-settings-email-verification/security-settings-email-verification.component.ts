import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, lastValueFrom, take } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { Logger as LoggerClass } from '../../../../../core/helpers/logger';
import { SettingsSectionComponent } from '../../../components/settings-section/settings-section.component';
import { SecuritySettingsActiveTokensComponent } from '../security-settings-active-tokens/security-settings-active-tokens.component';

const Logger = new LoggerClass('Settings');

@Component({
  selector: 'app-security-settings-email-verification',
  templateUrl: './security-settings-email-verification.component.html',
  styleUrls: ['./security-settings-email-verification.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    SettingsSectionComponent,
    SecuritySettingsActiveTokensComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SecuritySettingsEmailVerificationComponent {
  isLoading$ = new BehaviorSubject<boolean>(false);

  constructor(private api: ApiService, public auth: AuthService, private snackbar: SnackbarService) {}

  async resendVerificationEmail() {
    this.isLoading$.next(true);

    try {
      await lastValueFrom(this.api.auth.resendVerificationEmail().pipe(take(1)));

      this.snackbar.info('messages.verificationEmailSent', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        defaultMessage: 'messages.errors.sendingVerificationEmail',
        translateMessage: true,
      }).message;

      Logger.error('Error sending verification email:', errorMessage, e);
    }

    this.isLoading$.next(false);
  }
}
