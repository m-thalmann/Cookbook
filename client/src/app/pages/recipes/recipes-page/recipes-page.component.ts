import { ChangeDetectionStrategy, Component } from '@angular/core';
import { PageEvent } from '@angular/material/paginator';
import { ActivatedRoute, Router } from '@angular/router';
import { BehaviorSubject, combineLatest, debounceTime, map, Observable, switchMap, tap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { FilterOption } from 'src/app/core/models/filter-option';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { SortOption } from 'src/app/core/models/sort-option';

interface RecipeFilters {
  all?: boolean;
  search?: string;
  category?: string;
  sort?: SortOption[];
  pagination?: PaginationOptions;
}

@Component({
  selector: 'app-recipes-page',
  templateUrl: './recipes-page.component.html',
  styleUrls: ['./recipes-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipesPageComponent {
  availableSortOptions = [
    { column: 'name', name: 'Name', icon: 'drive_file_rename_outline' },
    { column: 'difficulty', name: 'Difficulty', icon: 'local_fire_department' },
    { column: 'created_at', name: 'Create date', icon: 'event_available' },
    { column: 'updated_at', name: 'Update date', icon: 'edit_calendar' },
  ];

  recipesLoading$ = new BehaviorSubject<boolean>(false);

  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: 12 });

  filters$: Observable<RecipeFilters> = this.route.queryParams.pipe(
    tap(() => {
      this.paginationOptions$.next({ page: 1, perPage: this.paginationOptions$.value.perPage });
    }),
    map((params) => {
      let all: boolean | undefined = undefined;
      let sort: SortOption;

      if (typeof params['all'] !== 'undefined') {
        all = ['1', 'true', 'yes'].includes(params['all']);
      }

      if (typeof params['sort'] !== 'undefined') {
        sort = { column: params['sort'], dir: params['sort-dir'] === 'desc' ? 'desc' : 'asc' };
      } else {
        sort = { column: 'created_at', dir: 'desc' };
      }

      return { all: all, search: params['search'], category: params['category'], sort: [sort] };
    })
  );

  recipes$ = combineLatest([this.filters$, this.paginationOptions$, this.auth.isAuthenticated$]).pipe(
    debounceTime(5), // to prevent reloading if filter is changed since then pagination is changed as well
    tap(() => this.recipesLoading$.next(true)),
    switchMap(([filters, paginationOptions, _]) => {
      let filtersOptions: FilterOption[] = [];

      if (filters.category) {
        filtersOptions.push({ column: 'category', value: filters.category });
      }

      return this.api.recipes.getList({
        all: filters.all,
        sort: filters.sort,
        search: filters.search,
        filters: filtersOptions,
        pagination: paginationOptions,
      });
    }),
    tap(() => this.recipesLoading$.next(false))
  );

  categories$ = this.auth.isAuthenticated$.pipe(switchMap(() => this.api.categories.getList()));

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private api: ApiService,
    public auth: AuthService
  ) {}

  getSortNameForColumn(column?: string) {
    return this.availableSortOptions.find((sortOption) => sortOption.column === column)?.name || column;
  }

  doSearch(search: string) {
    this.applyFilterParams({ search: search.length > 0 ? search : null });
  }

  onChangeAll(all: boolean) {
    this.applyFilterParams({ all: all ? '1' : null });
  }

  toggleSortDir(currentDir?: 'desc' | 'asc') {
    const dir = currentDir === 'desc' ? 'asc' : 'desc';

    this.applyFilterParams({ 'sort-dir': dir });
  }

  doSort(column: string) {
    this.applyFilterParams({ sort: column });
  }

  doFilterByCategory(category: string | null) {
    this.applyFilterParams({ category: category });
  }

  onPagination(page: PageEvent) {
    this.paginationOptions$.next({ page: page.pageIndex + 1, perPage: page.pageSize });
  }

  private applyFilterParams(params: { [key: string]: string | null }) {
    this.router.navigate([], { queryParams: params, queryParamsHandling: 'merge' });
  }
}
