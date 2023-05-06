import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTooltipModule } from '@angular/material/tooltip';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, lastValueFrom, map, merge, shareReplay, switchMap, switchScan, take, tap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { Logger as LoggerClass } from '../../../../../core/helpers/logger';
import { SettingsSectionComponent } from '../../../components/settings-section/settings-section.component';
import { SecuritySettingsActiveTokenCardComponent } from '../security-settings-active-token-card/security-settings-active-token-card.component';

const Logger = new LoggerClass('Settings');

@Component({
  selector: 'app-security-settings-active-tokens',
  templateUrl: './security-settings-active-tokens.component.html',
  styleUrls: ['./security-settings-active-tokens.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    MatTooltipModule,
    MatProgressSpinnerModule,
    SettingsSectionComponent,
    SecuritySettingsActiveTokenCardComponent,
    ErrorDisplayComponent,
    RepeatDirective,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SecuritySettingsActiveTokensComponent {
  isLoading$ = new BehaviorSubject<boolean>(false);
  isUpdating$ = new BehaviorSubject<boolean>(false);

  private refresh$ = new EventEmitter<void>();

  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: 6 });

  activeTokens$ = merge(this.auth.user$, this.refresh$).pipe(
    switchMap(() => {
      this.paginationOptions$.next({ ...this.paginationOptions$.value, page: 1 });

      return this.paginationOptions$.pipe(
        tap(() => this.isLoading$.next(true)),
        switchScan(
          (acc, paginationOptions) => {
            return this.api.auth.tokens.getList(paginationOptions).pipe(
              map((response) => ({
                totalItems: response.body!.meta.total,
                hasMoreItems: response.body!.meta.current_page < response.body!.meta.last_page,
                items: [...acc.items, ...response.body!.data],
              }))
            );
          },
          { totalItems: 0, hasMoreItems: true, items: [] }
        ),
        tap(() => this.isLoading$.next(false))
      );
    }),

    handledErrorInterceptor(),
    shareReplay(1)
  );

  error$ = this.api.handleRequestError(this.activeTokens$);

  constructor(private api: ApiService, private auth: AuthService, private snackbar: SnackbarService) {}

  nextPage() {
    this.paginationOptions$.next({ ...this.paginationOptions$.value, page: this.paginationOptions$.value.page + 1 });
  }

  async logoutFromSession(tokenId: number) {
    this.isUpdating$.next(true);

    try {
      await lastValueFrom(this.api.auth.tokens.delete(tokenId).pipe(take(1)));

      this.snackbar.info('messages.loggedOutFromSelectedSession', { translateMessage: true });

      this.refresh$.emit();
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        defaultMessage: 'messages.errors.loggingOutFromSelectedSession',
        translateMessage: true,
      }).message;

      Logger.error('Error deleting token:', errorMessage, e);
    }

    this.isUpdating$.next(false);
  }

  async logoutFromAllSessions() {
    this.isUpdating$.next(true);

    try {
      await lastValueFrom(this.api.auth.tokens.deleteAll().pipe(take(1)));

      this.snackbar.info('messages.loggedOutFromAllSessions', { translateMessage: true });

      this.refresh$.emit();
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        defaultMessage: 'messages.errors.loggingOutFromAllSessions',
        translateMessage: true,
      }).message;

      Logger.error('Error deleting all tokens:', errorMessage, e);
    }

    this.isUpdating$.next(false);
  }
}
