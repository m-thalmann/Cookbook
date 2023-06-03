import { CommonModule } from '@angular/common';
import { HttpErrorResponse, HttpStatusCode } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { BehaviorSubject } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { EditIngredientData } from 'src/app/core/models/ingredient';
import { DetailedRecipe, EditRecipeData, EditRecipeFormData } from 'src/app/core/models/recipe';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { EditRecipeDetailsFormComponent } from '../../../components/edit-recipe-details-form/edit-recipe-details-form.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-edit-recipe-details',
  standalone: true,
  imports: [CommonModule, EditRecipeDetailsFormComponent],
  templateUrl: './edit-recipe-details.component.html',
  styleUrls: ['./edit-recipe-details.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EditRecipeDetailsComponent {
  @Input()
  @CoerceBooleanProperty()
  disabled: any = false;

  @Input() recipe!: DetailedRecipe | null;

  @Output() updateRecipe = new EventEmitter<void>();
  @Output() saving = new BehaviorSubject<boolean>(false);

  serverErrorResponse$ = new BehaviorSubject<HttpErrorResponse | null>(null);

  constructor(private api: ApiService, private snackbar: SnackbarService) {}

  async onSave(currentRecipe: DetailedRecipe, recipe: EditRecipeFormData) {
    if (this.saving.value || this.disabled) {
      return;
    }

    this.serverErrorResponse$.next(null);
    this.saving.next(true);

    let error: HttpErrorResponse | null = null;

    let recipeUpdated = false;
    let ingredientsUpdated = false;
    let hasError = false;

    try {
      recipeUpdated = await this.recipeUpdate(currentRecipe, recipe);
    } catch (e) {
      hasError = true;

      if (e instanceof HttpErrorResponse) {
        error = e;
      } else {
        const errorMessage = this.snackbar.exception(e, {}).message;

        Logger.error('Error updating recipe:', errorMessage, e);
      }
    }

    try {
      ingredientsUpdated = await this.ingredientsUpdate(currentRecipe, recipe);
    } catch (e) {
      hasError = true;

      if (e instanceof HttpErrorResponse && e.status === HttpStatusCode.UnprocessableEntity) {
        if (error === null) {
          error = e;
        } else {
          error.error.errors = { ...error.error.errors, ...e.error.errors };
        }
      } else {
        const errorMessage = this.snackbar.exception(e, {}).message;

        Logger.error('Error updating ingredients:', errorMessage, e);
      }
    }

    if (!hasError && (recipeUpdated || ingredientsUpdated)) {
      this.snackbar.info('messages.recipeUpdated', { translateMessage: true });
      this.updateRecipe.emit();
    }

    if (hasError) {
      if (error !== null) {
        this.serverErrorResponse$.next(error);
      }
    }

    this.saving.next(false);
  }

  private async recipeUpdate(currentRecipe: DetailedRecipe, recipe: EditRecipeFormData) {
    const updateRecipe: EditRecipeData = {};

    const checkForUpdateKeys = [
      'name',
      'is_public',
      'description',
      'category',
      'portions',
      'difficulty',
      'preparation',
      'preparation_time_minutes',
      'resting_time_minutes',
      'cooking_time_minutes',
      'cookbook_id',
    ] as const;

    checkForUpdateKeys.forEach((key) => {
      if (recipe[key] !== currentRecipe[key]) {
        updateRecipe[key] = recipe[key] as any;
      }
    });

    if (Object.keys(updateRecipe).length === 0) {
      return false;
    }

    await toPromise(this.api.recipes.update(currentRecipe.id, updateRecipe));

    return true;
  }

  private async ingredientsUpdate(currentRecipe: DetailedRecipe, recipe: EditRecipeFormData) {
    const updates: { ingredientIndex: number; promise: Promise<unknown> }[] = [];

    const currentIngredients = currentRecipe.ingredients.reduce(
      (accIngredients, ingredientGroup) => [...accIngredients, ...ingredientGroup.items],
      [] as DetailedRecipe['ingredients'][0]['items']
    );
    const editIngredients = recipe.ingredients;

    const currentIngredientIds = currentIngredients.map((ingredient) => ingredient.id);
    const editIngredientIds = editIngredients.map((ingredient) => ingredient.recipeIngredientId);

    editIngredients.forEach((ingredient, index) => {
      const currentIngredientIndex =
        ingredient.recipeIngredientId === null ? -1 : currentIngredientIds.indexOf(ingredient.recipeIngredientId);

      if (currentIngredientIndex === -1) {
        updates.push({
          ingredientIndex: index,
          promise: toPromise(this.api.ingredients.create(currentRecipe.id, ingredient), true),
        });
        return;
      }

      const currentIngredient = currentIngredients[currentIngredientIndex];

      const updateIngredient: EditIngredientData = {};

      const checkForUpdateKeys = ['name', 'amount', 'unit', 'group'] as const;

      checkForUpdateKeys.forEach((key) => {
        if (ingredient[key] !== currentIngredient[key]) {
          updateIngredient[key] = ingredient[key] as any;
        }
      });

      if (Object.keys(updateIngredient).length === 0) {
        return;
      }

      updates.push({
        ingredientIndex: index,
        promise: toPromise(this.api.ingredients.update(currentIngredient.id, updateIngredient), true),
      });
    });

    currentIngredientIds.forEach((ingredientId, index) => {
      if (editIngredientIds.includes(ingredientId)) {
        return;
      }

      updates.push({
        ingredientIndex: index,
        promise: toPromise(this.api.ingredients.delete(ingredientId), true),
      });
    });

    if (updates.length === 0) {
      return false;
    }

    const validationErrors: { [key: string]: string[] } = {};
    let generalError: HttpErrorResponse | null = null;

    (await Promise.allSettled(updates.map((update) => update.promise))).forEach((result, index) => {
      if (result.status === 'fulfilled') {
        return;
      }

      const ingredientIndex = updates[index].ingredientIndex;
      const ingredient = editIngredients[ingredientIndex];

      Logger.error(`Error updating ingredient #${ingredientIndex} (${ingredient.name}):`, result.reason.error);

      if (result.reason instanceof HttpErrorResponse && result.reason.status === HttpStatusCode.UnprocessableEntity) {
        const errors: { [key: string]: string[] } = result.reason.error.errors;

        Object.keys(errors).forEach((key) => {
          validationErrors[`ingredients.${ingredientIndex}.${key}`] = errors[key];
        });
      } else if (generalError === null) {
        generalError = result.reason;
      }
    });

    if (Object.keys(validationErrors).length > 0) {
      throw new HttpErrorResponse({
        error: {
          errors: validationErrors,
        },
        status: HttpStatusCode.UnprocessableEntity,
        statusText: 'Unprocessable Entity',
      });
    }

    if (generalError !== null) {
      throw generalError;
    }

    return true;
  }
}
