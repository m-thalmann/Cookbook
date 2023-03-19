import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { BehaviorSubject, lastValueFrom } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { RouteHelperService } from 'src/app/core/services/route-helper.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'app-recipe-detail',
  templateUrl: './recipe-detail.component.html',
  styleUrls: ['./recipe-detail.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailComponent {
  @Input() recipe!: DetailedRecipe;

  isLoading$ = new BehaviorSubject<boolean>(false);

  portionsMultiplier: number = 1;

  constructor(private api: ApiService, private snackbar: SnackbarService, public routeHelper: RouteHelperService) {}

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

  portionsMultiplierStepFunction(currentValue: number, direction: 'up' | 'down') {
    let step = 1;

    if (currentValue < 1 || (direction === 'down' && currentValue === 1)) {
      step = 0.25;
    } else if (currentValue < 4 || (direction === 'down' && currentValue === 4)) {
      step = 0.5;
    }

    return step * (direction === 'up' ? 1 : -1);
  }

  async deleteRecipe() {
    this.isLoading$.next(true);

    try {
      await lastValueFrom(this.api.recipes.delete(this.recipe.id));

      this.snackbar.info({ message: 'Recipe moved to trash successfully' });

      this.routeHelper.navigateBack();
    } catch (e) {
      this.snackbar.warn({ message: 'Error moving recipe to trash', duration: null });
    } finally {
      this.isLoading$.next(false);
    }
  }
}
