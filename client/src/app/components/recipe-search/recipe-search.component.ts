import { CommonModule } from '@angular/common';
import { HttpResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatSlideToggleModule } from '@angular/material/slide-toggle';
import { MatTooltipModule } from '@angular/material/tooltip';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import {
  BehaviorSubject,
  Observable,
  combineLatest,
  debounceTime,
  distinctUntilChanged,
  map,
  shareReplay,
  switchMap,
  tap,
} from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { PaginationMeta } from 'src/app/core/models/pagination-meta';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { ListRecipe, RecipeFilters } from 'src/app/core/models/recipe';
import { SortOption } from 'src/app/core/models/sort-option';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { ErrorDisplayComponent } from '../error-display/error-display.component';
import { RecipeCardComponent } from '../recipe-card/recipe-card.component';
import { SearchBarComponent } from '../search-bar/search-bar.component';
import { SkeletonComponent } from '../skeleton/skeleton.component';
import { RecipeSearchFilterComponent } from './components/recipe-search-filter/recipe-search-filter.component';

@Component({
  selector: 'app-recipe-search',
  templateUrl: './recipe-search.component.html',
  styleUrls: ['./recipe-search.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatIconModule,
    MatSlideToggleModule,
    MatPaginatorModule,
    MatTooltipModule,
    SearchBarComponent,
    RecipeSearchFilterComponent,
    RecipeCardComponent,
    ErrorDisplayComponent,
    SkeletonComponent,
    RepeatDirective,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeSearchComponent {
  @Input() fetchRecipesFn!: (
    filters: RecipeFilters,
    paginationOptions: PaginationOptions,
    userIsAuthenticated: boolean
  ) => Observable<
    HttpResponse<{
      data: ListRecipe[];
      meta: PaginationMeta;
    }>
  >;

  @Input() fetchCategoriesFn!: (
    allCategories: boolean,
    userIsAuthenticated: boolean
  ) => Observable<
    HttpResponse<{
      data: string[];
    }>
  >;

  @Input() showAllFilter = true;

  readonly availableSortOptions = [
    { column: 'name', nameTranslateKey: 'general.name', icon: 'drive_file_rename_outline' },
    { column: 'difficulty', nameTranslateKey: 'recipes.difficulty', icon: 'local_fire_department' },
    { column: 'created_at', nameTranslateKey: 'general.createDate', icon: 'event_available' },
    { column: 'updated_at', nameTranslateKey: 'general.updateDate', icon: 'edit_calendar' },
  ];

  recipesLoading$ = new BehaviorSubject<boolean>(true);

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
        sort = { column: params['sort'], dir: 'asc' };
      } else {
        sort = { column: 'created_at', dir: 'desc' };
      }

      if (typeof params['sort-dir'] !== 'undefined') {
        sort.dir = params['sort-dir'] === 'desc' ? 'desc' : 'asc';
      }

      return { all: all, search: params['search'], category: params['category'], sort: [sort] };
    })
  );

  recipes$ = combineLatest([this.filters$, this.paginationOptions$, this.auth.user$]).pipe(
    debounceTime(5), // to prevent reloading if filter is changed since then pagination is changed as well
    tap(() => this.recipesLoading$.next(true)),
    switchMap(([filters, paginationOptions, authUser]) => this.fetchRecipesFn(filters, paginationOptions, !!authUser)),
    tap(() => this.recipesLoading$.next(false)),
    handledErrorInterceptor(),
    shareReplay(1)
  );

  categories$ = combineLatest([
    this.filters$.pipe(
      map((filters) => filters.all),
      distinctUntilChanged()
    ),
    this.auth.user$,
  ]).pipe(
    switchMap(([allCategories, authUser]) => this.fetchCategoriesFn(!!allCategories, !!authUser)),
    handledErrorInterceptor(),
    shareReplay(1)
  );

  recipesError$ = ApiService.handleRequestError(this.recipes$);
  categoriesError$ = ApiService.handleRequestError(this.categories$);

  constructor(private router: Router, private route: ActivatedRoute, public auth: AuthService) {}

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

  doSort(eventTarget: EventTarget) {
    this.applyFilterParams({ sort: (eventTarget as HTMLSelectElement).value });
  }

  doFilterByCategory(eventTarget: EventTarget | null) {
    const category = (eventTarget as HTMLSelectElement)?.value;

    this.applyFilterParams({ category: !category ? null : category });
  }

  onPagination(page: PageEvent) {
    this.paginationOptions$.next({ page: page.pageIndex + 1, perPage: page.pageSize });
  }

  private applyFilterParams(params: { [key: string]: string | null }) {
    this.router.navigate([], { queryParams: params, queryParamsHandling: 'merge' });
  }
}
