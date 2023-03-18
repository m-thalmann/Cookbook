import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { ListRecipe } from 'src/app/core/models/recipe';
import { PLACEHOLDER_RECIPE_IMAGE_URL } from 'src/app/core/models/recipe-image';

@Component({
  selector: 'app-recipe-card',
  templateUrl: './recipe-card.component.html',
  styleUrls: ['./recipe-card.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeCardComponent {
  @Input() recipe!: ListRecipe;

  constructor() {}

  get thumbnailImageCSS() {
    const url = this.recipe.thumbnail?.url || PLACEHOLDER_RECIPE_IMAGE_URL;

    return `background-image: url(${url})`;
  }
}
