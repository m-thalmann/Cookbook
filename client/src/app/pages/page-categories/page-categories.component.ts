import { Component, OnDestroy } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiOptions, CategoryInformation } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { SubSink } from 'src/app/core/functions';

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

  private subSink = new SubSink();

  constructor(private route: ActivatedRoute, private api: ApiService, private user: UserService) {
    this.subSink.push(
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

    this.subSink.push(
      this.user.userChanged.subscribe(() => {
        if (this.isOverview) {
          this.loadCategories();
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
    return '/categories/' + category;
  }

  getThumbnailUrl(recipeId: number | null) {
    if (recipeId !== null) {
      return this.api.getRecipeImageURL(recipeId, 0, 500);
    } else {
      return 'assets/images/cookbook.svg';
    }
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
