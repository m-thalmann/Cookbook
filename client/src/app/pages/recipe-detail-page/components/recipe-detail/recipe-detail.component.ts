import { Location } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { Router } from '@angular/router';
import { DetailedRecipe } from 'src/app/core/models/recipe';

@Component({
  selector: 'app-recipe-detail',
  templateUrl: './recipe-detail.component.html',
  styleUrls: ['./recipe-detail.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailComponent {
  @Input() recipe!: DetailedRecipe;

  portionsMultiplier: number = 1;

  constructor(private location: Location, private router: Router) {}

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

  getIngredientAmount(amount: number | null) {
    if (amount === null) {
      return null;
    }

    amount *= this.portionsMultiplier;

    return Math.round(amount * 100) / 100;
  }

  navigateBack() {
    if (document.referrer.indexOf(window.location.host) !== -1) {
      this.location.back();
    } else {
      this.router.navigateByUrl('/');
    }
  }

  portionsMultiplierStepFunction(currentValue: number, direction: 'up' | 'down') {
    let step = 1;

    if (currentValue < 1 || (direction === 'down' && currentValue === 1)) {
      step = 0.25;
    } else if (currentValue < 4 || (direction === 'down' && currentValue === 4)) {
      step = 0.5;
    }

    return step * (direction === 'up' ? 1 : -1);
  }
}
