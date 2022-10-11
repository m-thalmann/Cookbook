import { Location } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { DetailedRecipe } from 'src/app/core/models/recipe';

@Component({
  selector: 'app-recipe-detail',
  templateUrl: './recipe-detail.component.html',
  styleUrls: ['./recipe-detail.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailComponent {
  @Input() recipe!: DetailedRecipe;

  constructor(private location: Location) {
  }

  get totalTime() {
    if (
      this.recipe.preparation_time_minutes === null &&
      this.recipe.resting_time_minutes === null &&
      this.recipe.cooking_time_minutes === null
    ) {
      return null;
    }

    return (
      (this.recipe.preparation_time_minutes || 0) +
      (this.recipe.resting_time_minutes || 0) +
      (this.recipe.cooking_time_minutes || 0)
    );
  }

  navigateBack() {
    this.location.back();
  }
}

