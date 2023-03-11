import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { FilterOption } from 'src/app/core/models/filter-option';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { RecipeFilters } from 'src/app/core/models/recipe';

@Component({
  selector: 'app-recipes-page',
  templateUrl: './recipes-page.component.html',
  styleUrls: ['./recipes-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipesPageComponent {
  constructor(private api: ApiService) {}

  fetchRecipesFn = (filters: RecipeFilters, paginationOptions: PaginationOptions, userIsAuthenticated: boolean) => {
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
  };

  fetchCategoriesFn = (allCategories: boolean) => {
    return this.api.categories.getList(allCategories);
  };
}
