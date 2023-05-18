import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, HostBinding, Input } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject } from 'rxjs';
import { NumberInputComponent } from 'src/app/components/number-input/number-input.component';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { ApiService } from 'src/app/core/api/api.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { AmountPipe } from 'src/app/core/pipes/amount.pipe';
import { RouteHelperService } from 'src/app/core/services/route-helper.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { RecipeDetailHeaderComponent } from '../recipe-detail-header/recipe-detail-header.component';
import { RecipeDetailImageSliderComponent } from '../recipe-detail-image-slider/recipe-detail-image-slider.component';
import { RecipeDetailIngredientsComponent } from '../recipe-detail-ingredients/recipe-detail-ingredients.component';
import { RecipeDetailPreparationContentComponent } from '../recipe-detail-preparation-content/recipe-detail-preparation-content.component';
import { RecipeDetailSectionComponent } from '../recipe-detail-section/recipe-detail-section.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-recipe-detail',
  templateUrl: './recipe-detail.component.html',
  styleUrls: ['./recipe-detail.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    TranslocoModule,
    MatTooltipModule,
    MatButtonModule,
    MatIconModule,
    RecipeDetailImageSliderComponent,
    RecipeDetailHeaderComponent,
    RecipeDetailSectionComponent,
    RecipeDetailIngredientsComponent,
    RecipeDetailPreparationContentComponent,
    NumberInputComponent,
    SkeletonComponent,
    AmountPipe,
    RepeatDirective,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailComponent {
  @Input() recipe!: DetailedRecipe | null;

  isLoading$ = new BehaviorSubject<boolean>(false);

  portionsMultiplier: number = 1;

  constructor(private api: ApiService, private snackbar: SnackbarService, public routeHelper: RouteHelperService) {}

  get totalTime() {
    if (
      !this.recipe ||
      (this.recipe.preparation_time_minutes === null &&
        this.recipe.resting_time_minutes === null &&
        this.recipe.cooking_time_minutes === null)
    ) {
      return null;
    }

    return (
      (this.recipe.preparation_time_minutes || 0) +
      (this.recipe.resting_time_minutes || 0) +
      (this.recipe.cooking_time_minutes || 0)
    );
  }

  @HostBinding('class.no-images')
  get hasNoImages() {
    return this.recipe && this.recipe.images.length === 0;
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
      await toPromise(this.api.recipes.delete(this.recipe!.id));

      this.snackbar.info('messages.recipeMovedToTrash', { translateMessage: true });

      this.routeHelper.navigateBack();
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        translateMessage: true,
        defaultMessage: 'messages.errors.movingRecipeToTrash',
      }).message;

      Logger.error('Error moving recipe to trash:', errorMessage, e);
    }

    this.isLoading$.next(false);
  }
}
