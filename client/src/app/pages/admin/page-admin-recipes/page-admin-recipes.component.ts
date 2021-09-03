import { Component, OnDestroy, ViewChild } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subscription } from 'rxjs';
import { RecipeListComponent } from 'src/app/components/recipe-list/recipe-list.component';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiOptions, User } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-admin-recipes',
  templateUrl: './page-admin-recipes.component.html',
  styleUrls: ['./page-admin-recipes.component.scss'],
})
export class PageAdminRecipesComponent implements OnDestroy {
  @ViewChild('recipeList') recipeListComponent!: RecipeListComponent;

  loading = true;

  searchInput = '';
  private search: string | null = '';

  filterUserId: number | null = null;
  filterUser: User | null = null;

  private subscriptions: Subscription[] = [];

  constructor(private api: ApiService, private route: ActivatedRoute) {
    this.subscriptions.push(
      this.route.queryParams.subscribe((params) => {
        if (params.userId) {
          this.filterUserId = params.userId;
        } else {
          this.filterUserId = null;
        }
        this.loadFilterUser();

        if (!this.loading) {
          this.recipeListComponent.reload();
        }
      })
    );
  }

  async loadFilterUser() {
    if (this.filterUserId === null) {
      this.filterUser = null;
      return;
    }

    this.filterUser = null;

    let res = await this.api.admin.getUser(this.filterUserId);

    if (res.isOK() && res.value) {
      this.filterUser = res.value;
    }
  }

  doSearch() {
    this.search = this.searchInput.trim();

    if (this.search.length === 0) {
      this.search = null;
    }

    this.recipeListComponent.reload();
  }

  async reload(options: ApiOptions) {
    this.loading = true;
    let res = await this.api.admin.getRecipes(this.search, this.filterUserId, options);
    this.loading = false;

    return res;
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
