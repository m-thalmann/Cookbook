import { Location } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Router } from '@angular/router';
import { lastValueFrom } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { RecipePublicShareDialogComponent } from '../recipe-public-share-dialog/recipe-public-share-dialog.component';

@Component({
  selector: 'app-recipe-detail',
  templateUrl: './recipe-detail.component.html',
  styleUrls: ['./recipe-detail.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailComponent {
  @Input() recipe!: DetailedRecipe;

  portionsMultiplier: number = 1;

  constructor(
    private location: Location,
    private router: Router,
    private api: ApiService,
    private snackbar: SnackbarService,
    private dialog: MatDialog
  ) {}

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

  navigateBack() {
    if (document.referrer.indexOf(window.location.host) !== -1) {
      this.location.back();
    } else {
      this.router.navigateByUrl('/');
    }
  }

  openPublicShareDialog() {
    this.dialog.open(RecipePublicShareDialogComponent, { data: { recipe: this.recipe } });
  }

  async deleteRecipe() {
    try {
      await lastValueFrom(this.api.recipes.delete(this.recipe.id));

      this.snackbar.info('Recipe moved to trash successfully');

      this.navigateBack();
    } catch (e) {
      this.snackbar.error('Error moving recipe to trash');
    }
  }
}
