import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_CHECKBOX_DEFAULT_OPTIONS, MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatPaginatorModule, PageEvent } from '@angular/material/paginator';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSortModule, Sort } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, Observable, combineLatest, map, shareReplay, startWith, switchMap, tap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { SettingsSectionComponent } from 'src/app/components/settings-section/settings-section.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { FilterOption } from 'src/app/core/models/filter-option';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { ListRecipe, RecipeFilters } from 'src/app/core/models/recipe';
import { SortOption } from 'src/app/core/models/sort-option';
import { User } from 'src/app/core/models/user';
import { I18nDatePipe } from 'src/app/core/pipes/i18n-date.pipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { AdminRecipesUserFilterComponent } from './components/admin-recipes-user-filter/admin-recipes-user-filter.component';

const Logger = new LoggerClass('Admin');

@Component({
  selector: 'app-admin-recipes-page',
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
    MatCheckboxModule,
    ErrorDisplayComponent,
    SettingsSectionComponent,
    AdminRecipesUserFilterComponent,
    I18nDatePipe,
  ],
  templateUrl: './admin-recipes-page.component.html',
  styleUrls: ['./admin-recipes-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [{ provide: MAT_CHECKBOX_DEFAULT_OPTIONS, useValue: { clickAction: 'noop' } }],
})
export class AdminRecipesPageComponent {
  loading$ = new BehaviorSubject<boolean>(true);
  saving$ = new BehaviorSubject<boolean>(false);

  private reloadView$ = new EventEmitter<void>();

  filteredUser$ = new BehaviorSubject<User | null>(null);
  filteredUserLoading$ = new BehaviorSubject<boolean>(false);

  filters$: Observable<RecipeFilters> = this.route.queryParams.pipe(
    map((params) => {
      let sort: SortOption;

      if (typeof params['sort'] !== 'undefined') {
        sort = { column: params['sort'], dir: 'asc' };
      } else {
        sort = { column: 'created_at', dir: 'desc' };
      }

      if (typeof params['sort-dir'] !== 'undefined') {
        sort.dir = params['sort-dir'] === 'desc' ? 'desc' : 'asc';
      }

      return { search: params['search'], sort: [sort], userId: params['user-id'] };
    }),
    tap(async (filters) => {
      if (filters.userId === undefined) {
        this.filteredUser$.next(null);
        return;
      }
      if (filters.userId === this.filteredUser$.value?.id.toString()) {
        return;
      }

      this.filteredUserLoading$.next(true);

      try {
        const userResponse = await toPromise(this.api.users.get(filters.userId));

        this.filteredUser$.next(userResponse!.body!.data);
      } catch (e) {
        this.snackbar.warn('messages.errors.loadingUserInformation', { translateMessage: true });
      }

      this.filteredUserLoading$.next(false);
    }),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: 10 });

  recipes$ = combineLatest([this.filters$, this.auth.user$]).pipe(
    switchMap(([filters, _]) => {
      this.paginationOptions$.next({ ...this.paginationOptions$.value, page: 1 });

      return combineLatest([this.paginationOptions$, this.reloadView$.pipe(startWith(undefined))]).pipe(
        tap(() => this.loading$.next(true)),
        switchMap(([paginationOptions]) => {
          let filtersOptions: FilterOption[] = [];

          if (filters.userId !== undefined) {
            filtersOptions.push({ column: 'user_id', value: filters.userId });
          }

          return this.api.recipes.getList({
            all: true,
            includeDeleted: true,
            search: filters.search,
            filters: filtersOptions,
            sort: filters.sort,
            pagination: paginationOptions,
          });
        }),
        tap(() => this.loading$.next(false))
      );
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.recipes$);

  displayedColumns = ['id', 'name', 'user', 'created_at', 'deleted', 'actions'];

  constructor(
    private auth: AuthService,
    private api: ApiService,
    private route: ActivatedRoute,
    private router: Router,
    private snackbar: SnackbarService
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
    this.paginationOptions$.next({ page: page.pageIndex + 1, perPage: page.pageSize });
  }

  onUserFilter(user: User | null) {
    this.filteredUser$.next(user);
    this.applyFilterParams({ 'user-id': user?.id.toString() ?? null });
  }

  async updateRecipeDeleted(recipe: ListRecipe) {
    this.saving$.next(true);

    try {
      if (recipe.deleted_at === null) {
        await toPromise(this.api.recipes.delete(recipe.id));
      } else {
        await toPromise(this.api.recipes.trash.restoreRecipe(recipe.id));
      }

      this.reloadView$.emit();

      this.snackbar.info('messages.recipeUpdated', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error updating recipe:', errorMessage, e);
    }

    this.saving$.next(false);
  }

  trackByRecipe(index: number, recipe: ListRecipe) {
    return recipe.id;
  }

  private applyFilterParams(params: { [key: string]: string | null }) {
    this.router.navigate([], { queryParams: params, queryParamsHandling: 'merge' });
  }
}
