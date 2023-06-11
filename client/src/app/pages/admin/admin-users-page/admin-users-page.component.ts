import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_CHECKBOX_DEFAULT_OPTIONS, MatCheckboxModule } from '@angular/material/checkbox';
import { MatDialog } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatMenuModule } from '@angular/material/menu';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSortModule, Sort } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, combineLatest, shareReplay, startWith, switchMap, tap } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { PromptDialogComponent } from 'src/app/components/dialogs/prompt-dialog/prompt-dialog.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { SettingsSectionComponent } from 'src/app/components/settings-section/settings-section.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { SortOption } from 'src/app/core/models/sort-option';
import { DetailedUser, EditUserData } from 'src/app/core/models/user';
import { I18nDatePipe } from 'src/app/core/pipes/i18n-date.pipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Admin');

@Component({
  selector: 'app-admin-users-page',
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatButtonModule,
    MatIconModule,
    MatCheckboxModule,
    MatProgressSpinnerModule,
    MatMenuModule,
    MatFormFieldModule,
    MatInputModule,
    ErrorDisplayComponent,
    SettingsSectionComponent,
    I18nDatePipe,
  ],
  templateUrl: './admin-users-page.component.html',
  styleUrls: ['./admin-users-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [{ provide: MAT_CHECKBOX_DEFAULT_OPTIONS, useValue: { clickAction: 'noop' } }],
})
export class AdminUsersPageComponent {
  loading$ = new BehaviorSubject<boolean>(true);
  saving$ = new BehaviorSubject<boolean>(false);

  private resetList$ = new EventEmitter<void>();

  search$ = new BehaviorSubject<string | undefined>(undefined);
  sortOptions$ = new BehaviorSubject<SortOption[] | undefined>([{ column: 'id', dir: 'asc' }]);
  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: 10 });

  users$ = combineLatest([
    this.search$,
    this.sortOptions$,
    this.auth.user$,
    this.resetList$.pipe(startWith(undefined)),
  ]).pipe(
    switchMap(([search, sortOptions, _auth, _reset]) => {
      this.paginationOptions$.next({ ...this.paginationOptions$.value, page: 1 });

      return this.paginationOptions$.pipe(
        tap(() => this.loading$.next(true)),
        switchMap((paginationOptions) =>
          this.api.users.getList({ search, sort: sortOptions, pagination: paginationOptions })
        ),
        tap(() => this.loading$.next(false))
      );
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.users$);

  displayedColumns = ['id', 'name', 'email', 'email_verified_at', 'is_admin', 'created_at', 'actions'];

  constructor(
    public auth: AuthService,
    private api: ApiService,
    private snackbar: SnackbarService,
    private dialog: MatDialog,
    private transloco: TranslocoService
  ) {}

  onSearch(search: string) {
    this.search$.next(search.trim() || undefined);
  }

  onSort(sort: Sort) {
    if (sort.direction === '') {
      this.sortOptions$.next(undefined);
    } else {
      this.sortOptions$.next([{ column: sort.active, dir: sort.direction }]);
    }
  }

  onPagination(page: PageEvent) {
    this.paginationOptions$.next({ page: page.pageIndex + 1, perPage: page.pageSize });
  }

  async updateEmailVerified(user: DetailedUser) {
    await this.updateUser(user, { is_verified: !user.email_verified_at });
  }

  async updateIsAdmin(user: DetailedUser) {
    await this.updateUser(user, { is_admin: !user.is_admin });
  }

  async openCreateUserDialog() {
    // TODO: implement
  }

  async openEditEmailDialog(user: DetailedUser) {
    const email = await toPromise(
      this.dialog
        .open(PromptDialogComponent, {
          width: '400px',
          data: {
            title: this.transloco.translate('pages.admin.children.users.editEmail'),
            label: this.transloco.translate('general.email'),
            type: 'email',
          },
        })
        .afterClosed()
    );

    if (!email) {
      return;
    }

    await this.updateUser(user, { email });
  }

  async openEditPasswordDialog(user: DetailedUser) {
    const password = await toPromise(
      this.dialog
        .open(PromptDialogComponent, {
          width: '400px',
          data: {
            title: this.transloco.translate('pages.admin.children.users.editPassword'),
            label: this.transloco.translate('general.password'),
            type: 'password',
          },
        })
        .afterClosed()
    );

    if (!password) {
      return;
    }

    await this.updateUser(user, { password });
  }

  async deleteUser(userId: number) {
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

    this.saving$.next(true);

    try {
      await toPromise(this.api.users.delete(userId));
      this.resetList$.next();

      this.snackbar.info('messages.userRemoved', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error removing user:', errorMessage, e);
    }

    this.saving$.next(false);
  }

  trackByUser(index: number, user: DetailedUser) {
    return user.id;
  }

  private async updateUser(user: DetailedUser, data: EditUserData) {
    this.saving$.next(true);

    try {
      await toPromise(this.api.users.update(user.id, data));
      this.resetList$.next();

      this.snackbar.info('messages.userUpdated', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error updating user:', errorMessage, e);
    }

    this.saving$.next(false);
  }
}

