import { Component, Input } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { Recipe } from 'src/app/core/api/ApiInterfaces';

const FALLBACK_IMAGE = 'assets/images/cookbook.svg';

@Component({
  selector: 'cb-recipe-image-slider',
  templateUrl: './recipe-image-slider.component.html',
  styleUrls: ['./recipe-image-slider.component.scss'],
})
export class RecipeImageSliderComponent {
  @Input()
  set recipe(recipe: Recipe | null) {
    this.recipeImagesURLs = [];
    this.recipeImageThumbnailsURLs = [];

    if (recipe) {
      if (recipe.imagesCount > 0) {
        for (let i = 0; i < recipe.imagesCount; i++) {
          this.recipeImagesURLs.push(this.api.getRecipeImageURL(recipe.id, i, 960));
          this.recipeImageThumbnailsURLs.push(this.api.getRecipeImageURL(recipe.id, i, 75));
        }
      } else {
        this.recipeImagesURLs.push(FALLBACK_IMAGE);
        this.recipeImageThumbnailsURLs.push(FALLBACK_IMAGE);
      }
    }
  }

  recipeImagesURLs: string[] = [];
  recipeImageThumbnailsURLs: string[] = [];

  currentRecipeImageNumber = 0;

  constructor(private api: ApiService) {}

  get currentRecipeImageURL() {
    if (!this.recipeImagesURLs || this.recipeImagesURLs.length === 0) {
      return FALLBACK_IMAGE;
    }

    return this.recipeImagesURLs[this.currentRecipeImageNumber];
  }
}
