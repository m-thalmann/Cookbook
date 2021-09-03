import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { Subscription } from 'rxjs';
import { RecipeListComponent } from 'src/app/components/recipe-list/recipe-list.component';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiOptions } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-search',
  templateUrl: './page-search.component.html',
  styleUrls: ['./page-search.component.scss'],
})
export class PageSearchComponent implements OnInit, OnDestroy {
  @ViewChild('recipeList') recipeListComponent!: RecipeListComponent;

  search: string | null = null;

  searchInput: string | null = null;

  loading = false;

  private subscriptions: Subscription[] = [];

  constructor(private api: ApiService, private route: ActivatedRoute, private router: Router) {}

  ngOnInit() {
    this.subscriptions.push(
      this.route.params.subscribe((params) => {
        this.search = params.search ? params.search : null;

        if (this.search !== this.searchInput && this.search !== null) {
          this.loading = true;
        }

        this.searchInput = this.search;

        if (this.search && this.recipeListComponent) {
          this.recipeListComponent.reload();
        }
      })
    );
  }

  doSearch() {
    let search = this.searchInput?.trim();

    if (search) {
      this.router.navigate(['search', search]);
    }
  }

  reload = async (options: ApiOptions) => {
    this.loading = true;

    let res = await this.api.searchRecipes(this.search!, options);

    this.loading = false;

    return res;
  };

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
