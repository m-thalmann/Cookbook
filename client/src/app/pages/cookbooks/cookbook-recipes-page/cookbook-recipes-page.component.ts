import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { map, switchMap, take } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { FilterOption } from 'src/app/core/models/filter-option';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { RecipeFilters } from 'src/app/core/models/recipe';
import { CookbookHeaderComponent } from '../components/cookbook-header/cookbook-header.component';
import { RecipeSearchComponent } from 'src/app/components/recipe-search/recipe-search.component';

@Component({
  selector: 'app-cookbook-recipes-page',
  templateUrl: './cookbook-recipes-page.component.html',
  styleUrls: ['./cookbook-recipes-page.component.scss'],
  standalone: true,
  imports: [CommonModule, CookbookHeaderComponent, RecipeSearchComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookRecipesPageComponent {
  cookbookId$ = this.route.params.pipe(map((params) => params['id']));

  cookbook$ = this.cookbookId$.pipe(
    switchMap((id) => {
      return this.api.cookbooks.get(id);
    })
  );

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
