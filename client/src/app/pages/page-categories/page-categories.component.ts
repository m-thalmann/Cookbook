import { Component, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiOptions, CategoryInformation } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-categories',
  templateUrl: './page-categories.component.html',
  styleUrls: ['./page-categories.component.scss'],
})
export class PageCategoriesComponent implements OnDestroy {
  category: string | null = null;

  categories: CategoryInformation[] | null = null;

  loadingCategories = false;
  error = false;

  private subscriptions: Subscription[] = [];

  constructor(private route: ActivatedRoute, private api: ApiService) {
    this.subscriptions.push(
      this.route.params.subscribe((params) => {
        if (params.category) {
          this.category = decodeURIComponent(params.category);
          this.categories = null;
        } else {
          this.loadCategories();
          this.category = null;
        }
      })
    );
  }

  get isOverview() {
    return this.category === null;
  }

  reload = (options: ApiOptions) => {
    if (this.category) {
      return this.api.getRecipesForCategory(this.category, options);
    }

    return null;
  };

  async loadCategories() {
    this.loadingCategories = true;
    this.error = false;

    let res = await this.api.getCategories();

    if (res.isOK()) {
      this.categories = res.value;
    } else {
      this.error = true;
      console.error('Error loading categories:', res.error);
    }

    this.loadingCategories = false;
  }

  getCategoryURL(category: string) {
    return '/categories/' + encodeURIComponent(category);
  }

  getThumbnailUrl(recipeId: number | null) {
    if (recipeId !== null) {
      return this.api.getRecipeImageURL(recipeId, 0, 500);
    } else {
      return 'assets/images/cookbook.svg';
    }
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
