import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_CHECKBOX_DEFAULT_OPTIONS, MatCheckboxModule } from '@angular/material/checkbox';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatTableModule } from '@angular/material/table';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, combineLatest, map, of, shareReplay, startWith, switchMap, switchScan, tap } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { Cookbook, CookbookUser } from 'src/app/core/models/cookbook';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { I18nDatePipe } from 'src/app/core/pipes/i18n-date.pipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { AddCookbookUserDialogComponent } from '../add-cookbook-user-dialog/add-cookbook-user-dialog.component';

const Logger = new LoggerClass('Cookbooks');

@Component({
  selector: 'app-manage-cookbook-users',
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatProgressSpinnerModule,
    MatIconModule,
    MatCheckboxModule,
    MatButtonModule,
    MatTableModule,
    ErrorDisplayComponent,
    I18nDatePipe,
  ],
  templateUrl: './manage-cookbook-users.component.html',
  styleUrls: ['./manage-cookbook-users.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [{ provide: MAT_CHECKBOX_DEFAULT_OPTIONS, useValue: { clickAction: 'noop' } }],
})
export class ManageCookbookUsersComponent {
  @Input()
  set cookbook(cookbook: Cookbook | null) {
    this.cookbook$.next(cookbook);
  }
  get cookbook() {
    return this.cookbook$.value;
  }

  cookbook$ = new BehaviorSubject<Cookbook | null>(null);

  @Input()
  @CoerceBooleanProperty()
  disabled: any = false;

  private resetList$ = new EventEmitter<void>();

  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1 });

  users$ = combineLatest([this.cookbook$, this.resetList$.pipe(startWith(undefined))]).pipe(
    switchMap(([cookbook, _]) => {
      this.paginationOptions$.next({ ...this.paginationOptions$.value, page: 1 });

      if (!cookbook) {
        return of(null);
      }

      return this.paginationOptions$.pipe(
        tap(() => this.loading$.next(true)),
        switchScan(
          (acc, paginationOptions) => {
            return this.api.cookbooks.users
              .getList(cookbook.id, { pagination: paginationOptions, sort: [{ column: 'deleted_at', dir: 'asc' }] })
              .pipe(
                map((response) => ({
                  totalUsers: response.body!.meta.total,
                  hasMoreItems: response.body!.meta.current_page < response.body!.meta.last_page,
                  users: [...acc.users, ...response.body!.data],
                }))
              );
          },
          { totalUsers: 0, hasMoreItems: true, users: [] }
        ),
        tap(() => this.loading$.next(false))
      );
    }),

    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  loading$ = new BehaviorSubject<boolean>(true);

  @Output() saving = new BehaviorSubject<boolean>(false);

  error$ = this.api.handleRequestError(this.users$);

  @Output() authUserRemoved = new EventEmitter<void>();

  displayedColumns = ['name', 'email', 'created_at', 'is_admin', 'delete'];

  constructor(
    private api: ApiService,
    public auth: AuthService,
    private dialog: MatDialog,
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {}

  nextPage() {
    this.paginationOptions$.next({ ...this.paginationOptions$.value, page: this.paginationOptions$.value.page + 1 });
  }

  async doAddUser() {
    const userId: number | null | undefined = await toPromise(
      this.dialog.open(AddCookbookUserDialogComponent, { width: '350px' }).afterClosed()
    );

    if (userId == null) {
      return;
    }

    this.saving.next(true);

    try {
      await toPromise(this.api.cookbooks.users.create(this.cookbook!.id, userId, false));
      this.resetList$.next();

      this.snackbar.info('messages.userAdded', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error adding user:', errorMessage, e);
    }

    this.saving.next(false);
  }

  async updateIsAdmin(user: CookbookUser) {
    this.saving.next(true);

    try {
      await toPromise(this.api.cookbooks.users.update(this.cookbook!.id, user.id, !user.meta.is_admin));
      this.resetList$.next();

      this.snackbar.info('messages.userUpdated', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error updating user:', errorMessage, e);
    }

    this.saving.next(false);
  }

  async removeUser(userId: number) {
    const confirmed = await toPromise(
      this.dialog
        .open(ConfirmDialogComponent, {
          data: {
            title: this.transloco.translate('messages.areYouSure'),
            content: this.transloco.translate('messages.thisActionCantBeUndone'),
            btnConfirm: this.transloco.translate('actions.confirm'),
            btnDecline: this.transloco.translate('actions.abort'),
            warn: true,
          },
        })
        .afterClosed()
    );

    if (!confirmed) {
      return;
    }

    this.saving.next(true);

    try {
      await toPromise(this.api.cookbooks.users.delete(this.cookbook!.id, userId));
      this.resetList$.next();

      this.snackbar.info('messages.userRemoved', { translateMessage: true });

      const authUser = await toPromise(this.auth.user$);

      if (userId === authUser?.id) {
        this.authUserRemoved.next();
      }
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error removing user:', errorMessage, e);
    }

    this.saving.next(false);
  }

  trackByUser(index: number, user: CookbookUser) {
    return user.id;
  }
}
