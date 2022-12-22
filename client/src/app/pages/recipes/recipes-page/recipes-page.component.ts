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
  recipesLoading = false;

  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: 12 });

  filters$: Observable<RecipeFilters> = this.route.queryParams.pipe(
    tap(() => {
      this.paginationOptions$.next({ page: 1, perPage: this.paginationOptions$.value.perPage });
    }),
    map((params) => {
      let all: boolean | undefined = undefined;
      let sort: SortOption[] | undefined = undefined;

      if (typeof params['all'] !== 'undefined') {
        all = ['1', 'true', 'yes'].includes(params['all']);
      }

      if (typeof params['sort'] !== 'undefined') {
        sort = [{ column: params['sort'], dir: params['sort_dir'] === 'desc' ? 'desc' : 'asc' }];
      }

      return { all: all, search: params['search'], category: params['category'], sort: sort };
    })
  );

  recipes$ = combineLatest([this.filters$, this.paginationOptions$, this.auth.isAuthenticated$]).pipe(
    debounceTime(5), // to prevent reloading if filter is changed since then pagination is changed as well
    tap(() => (this.recipesLoading = true)),
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
    tap(() => (this.recipesLoading = false))
  );

  categories$ = this.auth.isAuthenticated$.pipe(switchMap(() => this.api.categories.getList()));

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private api: ApiService,
    private auth: AuthService
  ) {}

  doSearch(search: string) {
    this.router.navigate([], {
      queryParams: { search: search.length > 0 ? search : null },
      queryParamsHandling: 'merge',
    });
  }

  onPagination(page: PageEvent) {
    this.paginationOptions$.next({ page: page.pageIndex + 1, perPage: page.pageSize });
  }
}
