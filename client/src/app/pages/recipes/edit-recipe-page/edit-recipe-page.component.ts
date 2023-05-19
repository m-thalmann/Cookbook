import { CommonModule } from '@angular/common';
import { HttpErrorResponse, HttpStatusCode } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, EMPTY, combineLatest, of, shareReplay, startWith, switchMap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { EditIngredientData } from 'src/app/core/models/ingredient';
import { DetailedRecipe, EditRecipeData, EditRecipeFormData } from 'src/app/core/models/recipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { EditRecipeFormComponent } from '../components/edit-recipe-form/edit-recipe-form.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-edit-recipe-page',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    TranslocoModule,
    MatIconModule,
    MatButtonModule,
    EditRecipeFormComponent,
    ErrorDisplayComponent,
  ],
  templateUrl: './edit-recipe-page.component.html',
  styleUrls: ['./edit-recipe-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EditRecipePageComponent {
  private recipeId$ = combineLatest([this.activatedRoute.params, this.auth.user$]).pipe(
    switchMap(([params, _]) => {
      if (params['id']) {
        return of(params['id']);
      }

      Logger.error('EditRecipePage: No id defined');

      this.router.navigate(['/']);

      return EMPTY;
    })
  );

  private updateRecipe$ = new EventEmitter<void>();

  recipe$ = combineLatest([this.recipeId$, this.updateRecipe$.pipe(startWith(undefined))]).pipe(
    switchMap(([recipeId, _]) => this.api.recipes.get(recipeId)),
    switchMap((recipe) => {
      if (recipe.body?.data.user_can_edit) {
        return of(recipe);
      }

      Logger.error('EditRecipePage: User cannot edit recipe');
      this.snackbar.warn('messages.errors.userCantEditRecipe', { translateMessage: true });

      this.router.navigate(['/recipes', recipe.body!.data.id]);

      return EMPTY;
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.recipe$, (error) =>
    Logger.error('Error while loading recipe:', this.api.getErrorMessage(error), error)
  );

  saving$ = new BehaviorSubject<boolean>(false);
  serverErrorResponse$ = new BehaviorSubject<HttpErrorResponse | null>(null);

  constructor(
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private router: Router,
    private auth: AuthService,
    private snackbar: SnackbarService
  ) {}

  async onSave(currentRecipe: DetailedRecipe, recipe: EditRecipeFormData) {
    if (this.saving$.value) {
      return;
    }

    this.serverErrorResponse$.next(null);
    this.saving$.next(true);

    let error: HttpErrorResponse | null = null;

    let recipeUpdated = false;
    let ingredientsUpdated = false;
    let hasError = false;

    try {
      recipeUpdated = await this.updateRecipe(currentRecipe, recipe);
    } catch (e) {
      hasError = true;

      if (e instanceof HttpErrorResponse) {
        error = e;
      } else {
        const errorMessage = this.snackbar.exception(e, {});

        Logger.error('Error updating recipe:', errorMessage, e);
      }
    }

    try {
      ingredientsUpdated = await this.updateIngredients(currentRecipe, recipe);
    } catch (e) {
      hasError = true;

      if (e instanceof HttpErrorResponse && e.status === HttpStatusCode.UnprocessableEntity) {
        if (error === null) {
          error = e;
        } else {
          error.error.errors = { ...error.error.errors, ...e.error.errors };
        }
      } else {
        const errorMessage = this.snackbar.exception(e, {});

        Logger.error('Error updating ingredients:', errorMessage, e);
      }
    }

    if (!hasError && (recipeUpdated || ingredientsUpdated)) {
      this.updateRecipe$.next();
    }

    if (hasError) {
      if (error !== null) {
        this.serverErrorResponse$.next(error);
      }
    }

    this.saving$.next(false);
  }

  private async updateRecipe(currentRecipe: DetailedRecipe, recipe: EditRecipeFormData) {
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

  private async updateIngredients(currentRecipe: DetailedRecipe, recipe: EditRecipeFormData) {
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

