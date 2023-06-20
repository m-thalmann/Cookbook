import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Authentication');

@Component({
  selector: 'app-verify-email-page',
  templateUrl: './verify-email-page.component.html',
  styleUrls: ['./verify-email-page.component.scss'],
  standalone: true,
  imports: [TranslocoModule, MatProgressSpinnerModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class VerifyEmailPageComponent {
  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private api: ApiService,
    private auth: AuthService,
    private snackbar: SnackbarService
  ) {
    this.verifyEmail();
  }

  private async verifyEmail() {
    const routeSnapshot = this.route.snapshot;

    const userId = routeSnapshot.params['userId'];
    const token = routeSnapshot.params['token'];

    const expires = routeSnapshot.queryParams['expires'];
    const signature = routeSnapshot.queryParams['signature'];

    if (userId !== this.auth.user?.id) {
      this.snackbar.warn('messages.errors.wrongUserForVerification', { translateMessage: true });
      await this.router.navigateByUrl('/');
      return;
    }

    try {
      await toPromise(
        this.api.auth.verifyEmail(userId, token, {
          expires,
          signature,
        })
      );

      this.auth.setEmailVerified(true);

      this.snackbar.info('messages.emailVerified', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        defaultMessage: 'messages.errors.verifyingEmail',
        translateMessage: true,
      }).message;

      Logger.error('Error verifying email:', errorMessage, e);
    }

    await this.router.navigateByUrl('/');
  }
}
