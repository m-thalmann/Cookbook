import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { lastValueFrom, take } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
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
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {
    this.verifyEmail();
  }

  private async verifyEmail() {
    const routeSnapshot = this.route.snapshot;

    const userId = routeSnapshot.params['userId'];
    const token = routeSnapshot.params['token'];

    const expires = routeSnapshot.queryParams['expires'];
    const signature = routeSnapshot.queryParams['signature'];

    try {
      await lastValueFrom(
        this.api.auth
          .verifyEmail(userId, token, {
            expires,
            signature,
          })
          .pipe(take(1))
      );

      this.auth.setEmailVerified(true);

      this.snackbar.info({ message: this.transloco.translate('messages.emailVerified') });
    } catch (e) {
      const errorMessage = ApiService.getErrorMessage(e);

      this.snackbar.warn({ message: this.transloco.translate('messages.errors.verifyingEmail') });
      Logger.error('Error verifying email:', errorMessage, e);
    }

    await this.router.navigateByUrl('/');
  }
}

