import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { ListRecipe } from 'src/app/core/models/recipe';

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
    const url = this.recipe.thumbnail?.url || '/assets/images/placeholder.jpeg';

    return `background-image: url(${url})`;
  }
}

