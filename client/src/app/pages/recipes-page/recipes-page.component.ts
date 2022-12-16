import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { map, Observable, switchMap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
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
  filters$: Observable<RecipeFilters> = this.route.queryParams.pipe(
    map((params) => {
      let all = undefined;
      let sort = undefined;

      if (typeof params['all'] !== 'undefined') {
        all = ['1', 'true', 'yes'].includes(params['all']);
      }

      if (typeof params['sort'] !== 'undefined') {
        // TODO:
      }

      return { all: all, search: params['search'], category: params['category'], sort: sort };
    })
  );

  recipes$ = this.filters$.pipe(
    switchMap((filters) => {
      let filtersOptions: FilterOption[] = [];

      if (filters.category) {
        filtersOptions.push({ column: 'category', value: filters.category });
      }

      return this.api.recipes.getList({
        all: filters.all,
        sort: filters.sort,
        search: filters.search,
        filters: filtersOptions,
      });
    })
  );

  constructor(private router: Router, private route: ActivatedRoute, private api: ApiService) {}

  doSearch(search: string) {
    this.router.navigate([], {
      queryParams: { search: search.length > 0 ? search : null },
      queryParamsHandling: 'merge',
    });
  }
}

