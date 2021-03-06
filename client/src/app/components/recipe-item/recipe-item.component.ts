import { Component, Input } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { Recipe } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { calculateTotalTime, slugify } from 'src/app/core/functions';

const FALLBACK_IMAGE = 'assets/images/cookbook.svg';

@Component({
  selector: 'cb-recipe-item',
  templateUrl: './recipe-item.component.html',
  styleUrls: ['./recipe-item.component.scss'],
})
export class RecipeItemComponent {
  @Input() recipe: Recipe | null = null;

  @Input() compact = false;
  @Input() grid = false;
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

    return this.api.getRecipeImageURL(this.recipe.id, 0, 350, !this.recipe.public);
  }

  get totalTime() {
    return calculateTotalTime(this.recipe);
  }

  get isOwner() {
    return this.recipe && this.user.user?.id === this.recipe.user.id;
  }

  get canEdit() {
    if (!this.recipe) return false;

    return this.user.user?.isAdmin || this.user.user?.id === this.recipe.user.id;
  }
}
