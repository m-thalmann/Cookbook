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
  distinctUntilChanged,
  map,
  shareReplay,
  switchMap,
  tap,
} from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { PaginationMeta } from 'src/app/core/models/pagination-meta';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { ListRecipe, RecipeFilters } from 'src/app/core/models/recipe';
import { SortOption } from 'src/app/core/models/sort-option';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { ErrorDisplayComponent } from '../error-display/error-display.component';
import { NoItemsDisplayComponent } from '../no-items-display/no-items-display.component';
import { RecipeCardComponent } from '../recipe-card/recipe-card.component';
import { SearchBarComponent } from '../search-bar/search-bar.component';
import { SkeletonComponent } from '../skeleton/skeleton.component';
import { RecipeSearchFilterComponent } from './components/recipe-search-filter/recipe-search-filter.component';

interface AvailableSortOption {
  column: string;
  nameTranslateKey: string;
  icon: string;
}

type Filters = RecipeFilters & { paginationOptions: PaginationOptions };

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
    NoItemsDisplayComponent,
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

  @Input()
  @CoerceBooleanProperty()
  showAllFilter: any = true;

  readonly availableSortOptions: AvailableSortOption[] = [
    { column: 'name', nameTranslateKey: 'general.name', icon: 'drive_file_rename_outline' },
    { column: 'difficulty', nameTranslateKey: 'recipes.difficulty', icon: 'local_fire_department' },
    { column: 'created_at', nameTranslateKey: 'general.createDate', icon: 'event_available' },
    { column: 'updated_at', nameTranslateKey: 'general.updateDate', icon: 'edit_calendar' },
  ];

  recipesLoading$ = new BehaviorSubject<boolean>(true);

  filters$: Observable<Filters> = this.route.queryParams.pipe(
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

      const paginationOptions: PaginationOptions = {
        page: typeof params['page'] !== 'undefined' ? parseInt(params['page']) : 1,
        perPage: typeof params['per-page'] !== 'undefined' ? parseInt(params['per-page']) : 12,
      };

      return {
        all: all,
        search: params['search'],
        category: params['category'],
        sort: [sort],
        paginationOptions: paginationOptions,
      } as Filters;
    })
  );

  recipes$ = combineLatest([this.filters$, this.auth.user$]).pipe(
    tap(() => this.recipesLoading$.next(true)),
    switchMap(([filters, authUser]) => this.fetchRecipesFn(filters, filters.paginationOptions, !!authUser)),
    tap(() => this.recipesLoading$.next(false)),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
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
    shareReplay({ bufferSize: 1, refCount: true })
  );

  recipesError$ = this.api.handleRequestError(this.recipes$);
  categoriesError$ = this.api.handleRequestError(this.categories$);

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    public auth: AuthService,
    private api: ApiService
  ) {}

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
    this.applyFilterParams({ page: (page.pageIndex + 1).toString(), 'per-page': page.pageSize.toString() });
  }

  private applyFilterParams(params: { [key: string]: string | null }, resetPage = true) {
    if (resetPage && !('page' in params)) {
      params['page'] = null;
    }

    this.router.navigate([], { queryParams: params, queryParamsHandling: 'merge' });
  }

  trackBySortOption(index: number, option: AvailableSortOption) {
    return option.column;
  }

  trackByRecipe(index: number, recipe: ListRecipe) {
    return recipe.id;
  }
}
