import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { map, shareReplay, switchMap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { RecipeSearchComponent } from 'src/app/components/recipe-search/recipe-search.component';
import { ApiService } from 'src/app/core/api/api.service';
import { FilterOption } from 'src/app/core/models/filter-option';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { RecipeFilters } from 'src/app/core/models/recipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { CookbookHeaderComponent } from '../components/cookbook-header/cookbook-header.component';

@Component({
  selector: 'app-cookbook-recipes-page',
  templateUrl: './cookbook-recipes-page.component.html',
  styleUrls: ['./cookbook-recipes-page.component.scss'],
  standalone: true,
  imports: [CommonModule, TranslocoModule, CookbookHeaderComponent, RecipeSearchComponent, ErrorDisplayComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookRecipesPageComponent {
  cookbookId$ = this.route.params.pipe(map((params) => params['id']));

  cookbook$ = this.cookbookId$.pipe(
    switchMap((id) => {
      return this.api.cookbooks.get(id);
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.cookbook$);

  constructor(private route: ActivatedRoute, private api: ApiService) {}

  fetchRecipesFn = (filters: RecipeFilters, paginationOptions: PaginationOptions, userIsAuthenticated: boolean) => {
    let filtersOptions: FilterOption[] = [];

    if (filters.category) {
      filtersOptions.push({ column: 'category', value: filters.category });
    }

    return this.cookbookId$.pipe(
      switchMap((id) =>
        this.api.cookbooks.getRecipes(id, {
          sort: filters.sort,
          search: filters.search,
          filters: filtersOptions,
          pagination: paginationOptions,
        })
      )
    );
  };

  fetchCategoriesFn = () => {
    return this.cookbookId$.pipe(switchMap((id) => this.api.cookbooks.getCategories(id)));
  };
}
