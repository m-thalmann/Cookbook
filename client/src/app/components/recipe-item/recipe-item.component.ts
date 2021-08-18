import { Component, Input } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { Recipe } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { slugify } from 'src/app/core/functions';

const FALLBACK_IMAGE = 'assets/images/cookbook.svg';

@Component({
  selector: 'cb-recipe-item',
  templateUrl: './recipe-item.component.html',
  styleUrls: ['./recipe-item.component.scss'],
})
export class RecipeItemComponent {
  @Input() recipe: Recipe | null = null;

  @Input() disabled: boolean = false;

  constructor(private api: ApiService, private user: UserService) {}

  get recipeURL() {
    if (this.disabled || !this.recipe) return null;

    return `/recipes/${this.recipe.id}/${slugify(this.recipe.name)}`;
  }

  get categoryURL() {
    if (!this.recipe?.category) {
      return null;
    }

    return '/categories/' + encodeURIComponent(this.recipe?.category);
  }

  get thumbnail() {
    if (!this.recipe || this.recipe.imagesCount === 0) {
      return FALLBACK_IMAGE;
    }

    return this.api.getRecipeImageURL(this.recipe.id, 0, 250);
  }

  get totalTime() {
    let time = 0;

    if (this.recipe?.preparationTime) {
      time += this.recipe.preparationTime;
    }
    if (this.recipe?.restTime) {
      time += this.recipe.restTime;
    }
    if (this.recipe?.cookTime) {
      time += this.recipe.cookTime;
    }

    if (time > 0) {
      return time + ' min';
    } else {
      return null;
    }
  }

  get isOwner() {
    if (!this.recipe) return false;

    return this.user.user?.id === this.recipe?.user.id;
  }
}
