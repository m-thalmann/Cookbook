import { Component, Input } from '@angular/core';

const FALLBACK_IMAGE = 'assets/images/cookbook.svg';

@Component({
  selector: 'cb-recipe-image-slider',
  templateUrl: './recipe-image-slider.component.html',
  styleUrls: ['./recipe-image-slider.component.scss'],
})
export class RecipeImageSliderComponent {
  @Input() recipeImagesURLs: string[] | null = null;

  currentRecipeImageNumber = 0;

  constructor() {}

  get currentRecipeImageURL() {
    if (!this.recipeImagesURLs) {
      return FALLBACK_IMAGE;
    }

    return this.recipeImagesURLs[this.currentRecipeImageNumber];
  }
}
