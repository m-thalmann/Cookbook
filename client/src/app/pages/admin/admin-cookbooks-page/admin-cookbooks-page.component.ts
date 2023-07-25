import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSortModule, Sort } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, Observable, combineLatest, map, shareReplay, switchMap, tap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { SettingsSectionComponent } from 'src/app/components/settings-section/settings-section.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { CookbookFilters, CookbookWithCounts } from 'src/app/core/models/cookbook';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { SortOption } from 'src/app/core/models/sort-option';
import { I18nDatePipe } from 'src/app/core/pipes/i18n-date.pipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';

type Filters = CookbookFilters & { paginationOptions: PaginationOptions };

@Component({
  selector: 'app-admin-cookbooks-page',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    TranslocoModule,
    MatTableModule,
    MatPaginatorModule,
    MatSortModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    MatFormFieldModule,
    MatInputModule,
    ErrorDisplayComponent,
    SettingsSectionComponent,
    I18nDatePipe,
  ],
  templateUrl: './admin-cookbooks-page.component.html',
  styleUrls: ['./admin-cookbooks-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminCookbooksPageComponent {
  loading$ = new BehaviorSubject<boolean>(true);
  saving$ = new BehaviorSubject<boolean>(false);

  filters$: Observable<Filters> = this.route.queryParams.pipe(
    map((params) => {
      let sort: SortOption;

      if (typeof params['sort'] !== 'undefined') {
        sort = { column: params['sort'], dir: 'asc' };
      } else {
        sort = { column: 'id', dir: 'asc' };
      }

      if (typeof params['sort-dir'] !== 'undefined') {
        sort.dir = params['sort-dir'] === 'desc' ? 'desc' : 'asc';
      }

      const paginationOptions: PaginationOptions = {
        page: typeof params['page'] !== 'undefined' ? parseInt(params['page']) : 1,
        perPage: typeof params['per-page'] !== 'undefined' ? parseInt(params['per-page']) : 10,
      };

      return {
        search: params['search'],
        sort: [sort],
        paginationOptions: paginationOptions,
      } as Filters;
    })
  );

  cookbooks$ = combineLatest([this.filters$, this.auth.user$]).pipe(
    tap(() => this.loading$.next(true)),
    switchMap(([filters]) => this.api.cookbooks.getList(true, filters.paginationOptions, filters.sort, filters.search)),
    tap(() => this.loading$.next(false)),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.cookbooks$);

  displayedColumns = ['id', 'name', 'recipes', 'users', 'created_at', 'actions'];

  constructor(
    public auth: AuthService,
    private api: ApiService,
    private route: ActivatedRoute,
    private router: Router
  ) {}

  onSearch(search: string) {
    this.applyFilterParams({ search: search.length > 0 ? search : null });
  }

  onSort(sort: Sort) {
    if (sort.direction === '') {
      this.applyFilterParams({ sort: null });
    } else {
      this.applyFilterParams({ sort: sort.active, 'sort-dir': sort.direction });
    }
  }

  onPagination(page: PageEvent) {
    this.applyFilterParams({ page: (page.pageIndex + 1).toString(), 'per-page': page.pageSize.toString() });
  }

  trackByCookbook(index: number, cookbook: CookbookWithCounts) {
    return cookbook.id;
  }

  private applyFilterParams(params: { [key: string]: string | null }, resetPage = true) {
    if (resetPage && !('page' in params)) {
      params['page'] = null;
    }

    this.router.navigate([], { queryParams: params, queryParamsHandling: 'merge' });
  }
}
