import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatMenuModule } from '@angular/material/menu';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSortModule, Sort } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, combineLatest, debounceTime, shareReplay, switchMap, tap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { SettingsSectionComponent } from 'src/app/components/settings-section/settings-section.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { SortOption } from 'src/app/core/models/sort-option';
import { DetailedUser } from 'src/app/core/models/user';
import { I18nDatePipe } from 'src/app/core/pipes/i18n-date.pipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';

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
})
export class AdminUsersPageComponent {
  loading$ = new BehaviorSubject<boolean>(true);
  saving$ = new BehaviorSubject<boolean>(false);

  search$ = new BehaviorSubject<string | undefined>(undefined);
  sortOptions$ = new BehaviorSubject<SortOption[] | undefined>([{ column: 'id', dir: 'asc' }]);
  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: 10 });

  filters$ = combineLatest([this.search$, this.sortOptions$]).pipe(
    tap(() => {
      this.paginationOptions$.next({ page: 1, perPage: this.paginationOptions$.value.perPage });
    })
  );

  users$ = combineLatest([this.filters$, this.paginationOptions$, this.auth.user$]).pipe(
    debounceTime(5), // to prevent reloading if filter is changed since then pagination is changed as well
    tap(() => this.loading$.next(true)),
    switchMap(([filters, paginationOptions, _]) =>
      this.api.users.getList({ search: filters[0], sort: filters[1], pagination: paginationOptions })
    ),
    tap(() => this.loading$.next(false)),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.users$);

  displayedColumns = ['id', 'name', 'email', 'email_verified_at', 'is_admin', 'created_at', 'actions'];

  constructor(public auth: AuthService, private api: ApiService) {}

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

  openCreateUserDialog() {
    // TODO: implement
  }

  openEditEmailDialog(user: DetailedUser) {
    // TODO: implement
  }

  openEditPasswordDialog(user: DetailedUser) {
    // TODO: implement
  }

  deleteUser(userId: number) {
    // TODO: implement
  }
}

