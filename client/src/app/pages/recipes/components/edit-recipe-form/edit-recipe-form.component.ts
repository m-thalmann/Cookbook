import { coerceBooleanProperty } from '@angular/cdk/coercion';
import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, ElementRef, EventEmitter, Input, Output } from '@angular/core';
import { FormArray, FormBuilder, FormControl, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MAT_FORM_FIELD_DEFAULT_OPTIONS, MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSelectModule } from '@angular/material/select';
import { MatSliderModule } from '@angular/material/slider';
import { MatTooltipModule } from '@angular/material/tooltip';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, Observable, combineLatest, map, shareReplay, startWith, switchMap, tap } from 'rxjs';
import { EditorComponent } from 'src/app/components/editor/editor.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { trimAndNull } from 'src/app/core/helpers/trim-and-null';
import { CreateIngredientData, Ingredient } from 'src/app/core/models/ingredient';
import { CreateRecipeData, DetailedRecipe } from 'src/app/core/models/recipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { EditRecipeIngredientFormGroupComponent } from './components/edit-recipe-ingredient-form-group/edit-recipe-ingredient-form-group.component';

interface FormIngredientGroup {
  name: FormControl<string | null>;
  ingredients: FormArray<FormGroup<FormIngredient>>;
}

export interface FormIngredient {
  name: FormControl<string>;
  amount: FormControl<number | null>;
  unit: FormControl<string | null>;
}

@Component({
  selector: 'app-edit-recipe-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    TranslocoModule,
    MatTooltipModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatCheckboxModule,
    MatAutocompleteModule,
    MatSelectModule,
    MatSliderModule,
    MatIconModule,
    MatProgressSpinnerModule,
    EditRecipeIngredientFormGroupComponent,
    EditorComponent,
  ],
  templateUrl: './edit-recipe-form.component.html',
  styleUrls: ['./edit-recipe-form.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [
    { provide: MAT_FORM_FIELD_DEFAULT_OPTIONS, useValue: { subscriptSizing: 'dynamic', hideRequiredMarker: true } },
  ],
})
export class EditRecipeFormComponent {
  @Input()
  set disabled(disabled: any) {
    const isDisabled = coerceBooleanProperty(disabled);

    if (isDisabled) {
      this.form.disable();
    } else {
      this.form.enable();
    }

    this.disabled$.next(isDisabled);
  }

  disabled$ = new BehaviorSubject<boolean>(false);

  @Input()
  set loading(loading: any) {
    this._loading = coerceBooleanProperty(loading);
  }

  get loading() {
    return this._loading;
  }

  private _loading = false;

  @Input()
  set serverErrorResponse(errorResponse: HttpErrorResponse | null) {
    if (!errorResponse) {
      this.savingError$.next(null);
      return;
    }

    if (this.setServerValidationErrors(errorResponse)) {
      return;
    }

    this.savingError$.next(this.api.getErrorMessage(errorResponse));
  }

  savingError$ = new BehaviorSubject<string | null>(null);

  @Input()
  set recipe(recipe: DetailedRecipe | null) {
    this._recipe = recipe;

    this.resetForm();
  }

  get recipe() {
    return this._recipe;
  }

  private _recipe: DetailedRecipe | null = null;

  @Output() save = new EventEmitter<CreateRecipeData>();

  categories$ = this.auth.user$.pipe(
    switchMap(() => this.api.categories.getList()),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  cookbooks$ = this.auth.user$.pipe(
    tap(() => this.form.controls.cookbookId.disable()),
    switchMap(() => this.api.cookbooks.getEditableList()),
    tap(() => {
      if (!this.disabled$.value) {
        this.form.controls.cookbookId.enable();
      }
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  ingredients$ = this.auth.user$.pipe(
    switchMap(() => this.api.ingredients.getList()),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  cookbooksError$ = this.api.handleRequestError(this.cookbooks$);

  form = this.fb.nonNullable.group({
    name: [<string>'', [Validators.required]],
    isPublic: [<boolean>false, [Validators.required]],
    category: [<string | null>null],
    cookbookId: [<number | null>null],
    description: [<string | null>null],
    portions: [<number | null>null, [Validators.min(1)]],
    difficulty: [<number | null>null, [Validators.min(1), Validators.max(5)]],
    preparationTimeMinutes: [<number | null>null, [Validators.min(1)]],
    restingTimeMinutes: [<number | null>null, [Validators.min(1)]],
    cookingTimeMinutes: [<number | null>null, [Validators.min(1)]],
    ingredients: this.fb.nonNullable.array<FormGroup<FormIngredientGroup>>([]),
    preparation: [<string | null>null],
  });

  filteredCategories$: Observable<string[]> = combineLatest([
    this.form.controls.category.valueChanges.pipe(startWith(this.form.controls.category.value)),
    this.categories$,
  ]).pipe(
    map(([_, categoriesResponse]) => {
      const categories = categoriesResponse.body?.data;

      const filterCategory = (this.form.controls.category.value || '').toLowerCase();

      return categories?.filter((category) => category.toLowerCase().includes(filterCategory)) || [];
    }),
    startWith([]),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  amountOfIngredients$ = this.form.controls.ingredients.valueChanges.pipe(
    startWith(undefined),
    map(() =>
      Object.entries(this.form.controls.ingredients.controls).reduce(
        (acc, [_, formIngredients]) => formIngredients.controls.ingredients.length + acc,
        0
      )
    ),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  constructor(
    private fb: FormBuilder,
    private elementRef: ElementRef,
    private api: ApiService,
    private auth: AuthService
  ) {}

  resetForm() {
    if (!this.recipe) {
      this.form.reset();

      return;
    }

    this.form.patchValue({
      name: this.recipe.name,
      isPublic: this.recipe.is_public,
      category: this.recipe.category,
      cookbookId: this.recipe.cookbook_id,
      description: this.recipe.description,
      portions: this.recipe.portions,
      difficulty: this.recipe.difficulty,
      preparationTimeMinutes: this.recipe.preparation_time_minutes,
      restingTimeMinutes: this.recipe.resting_time_minutes,
      cookingTimeMinutes: this.recipe.cooking_time_minutes,
      ingredients: [],
      preparation: this.recipe.preparation,
    });

    const ingredientGroupIndices: { [key: string]: number } = {};

    this.recipe.ingredients.forEach((ingredientGroup) => {
      const groupName = ingredientGroup.group || '';

      if (ingredientGroupIndices[groupName] === undefined) {
        ingredientGroupIndices[groupName] = this.addIngredientGroup(ingredientGroup.group);
      }

      return ingredientGroup.items.forEach((ingredient) =>
        this.addIngredient(ingredientGroupIndices[groupName], ingredient)
      );
    });
  }

  difficultyValueFn(value: number | null) {
    if (value) {
      return value.toString();
    }

    return '-';
  }

  addIngredient(groupIndex: number, ingredient: Ingredient | null, focusIngredient = false) {
    const ingredientGroup = this.fb.nonNullable.group({
      name: [ingredient?.name ?? '', [Validators.required]],
      amount: [ingredient?.amount ?? null, [Validators.min(0.01)]],
      unit: [ingredient?.unit ?? null],
    });

    this.form.controls.ingredients.at(groupIndex).controls.ingredients.push(ingredientGroup);

    const index = this.form.controls.ingredients.at(groupIndex).controls.ingredients.length - 1;

    if (focusIngredient) {
      setTimeout(() => {
        this.getIngredientInputNativeElement(groupIndex, index, 'name')?.focus();
      });
    }

    return index;
  }

  removeIngredient(groupIndex: number, index: number) {
    const ingredientGroup = this.form.controls.ingredients.at(groupIndex).controls.ingredients;

    ingredientGroup.removeAt(index);

    if (ingredientGroup.length === 0) {
      this.form.controls.ingredients.removeAt(groupIndex);
    }
  }

  addIngredientGroup(groupName: string | null) {
    this.form.controls.ingredients.push(
      this.fb.nonNullable.group({
        name: [groupName, []],
        ingredients: this.fb.nonNullable.array<FormGroup<FormIngredient>>([]),
      })
    );

    return this.form.controls.ingredients.length - 1;
  }

  removeIngredientGroup(groupIndex: number) {
    this.form.controls.ingredients.removeAt(groupIndex);
  }

  onSubmit() {
    if (this.form.invalid) {
      return;
    }

    const ingredients: CreateIngredientData[] = this.form.controls.ingredients.controls.reduce(
      (allIngredients, ingredientGroup) => {
        const groupName = trimAndNull(ingredientGroup.controls.name.value);

        const groupIngredients = ingredientGroup.controls.ingredients.controls.map((ingredient) => ({
          name: ingredient.controls.name.value,
          amount: ingredient.controls.amount.value,
          unit: trimAndNull(ingredient.controls.unit.value),
          group: groupName,
        }));

        return [...allIngredients, ...groupIngredients];
      },
      [] as CreateIngredientData[]
    );

    const recipe: CreateRecipeData = {
      name: this.form.controls.name.value,
      is_public: this.form.controls.isPublic.value,
      category: trimAndNull(this.form.controls.category.value),
      cookbook_id: this.form.controls.cookbookId.value,
      description: trimAndNull(this.form.controls.description.value),
      portions: this.form.controls.portions.value,
      difficulty: this.form.controls.difficulty.value,
      preparation_time_minutes: this.form.controls.preparationTimeMinutes.value,
      resting_time_minutes: this.form.controls.restingTimeMinutes.value,
      cooking_time_minutes: this.form.controls.cookingTimeMinutes.value,
      preparation: trimAndNull(this.form.controls.preparation.value),
      ingredients,
    };

    this.save.emit(recipe);
  }

  trackByCookbook(index: number, cookbook: { id: number; name: string }) {
    return cookbook.id;
  }

  getIngredientKey(groupIndex: number, ingredientIndex: number) {
    return `${groupIndex}-${ingredientIndex}`;
  }

  private setServerValidationErrors(errorResponse: HttpErrorResponse) {
    const fieldsMap: { [key: string]: string } = {
      is_public: 'isPublic',
      preparation_time_minutes: 'preparationTimeMinutes',
      resting_time_minutes: 'restingTimeMinutes',
      cooking_time_minutes: 'cookingTimeMinutes',
      cookbook_id: 'cookbookId',
    };

    this.form.controls.ingredients.controls.reduce((ingredientIndexOffset, ingredientGroup, groupIndex) => {
      ingredientGroup.controls.ingredients.controls.forEach((ingredient, ingredientIndex) => {
        const index = ingredientIndexOffset + ingredientIndex;

        fieldsMap[`ingredients.${index}.name`] = `ingredients.${groupIndex}.ingredients.${ingredientIndex}.name`;
        fieldsMap[`ingredients.${index}.unit`] = `ingredients.${groupIndex}.ingredients.${ingredientIndex}.unit`;
        fieldsMap[`ingredients.${index}.amount`] = `ingredients.${groupIndex}.ingredients.${ingredientIndex}.amount`;
        fieldsMap[`ingredients.${index}.group`] = `ingredients.${groupIndex}.name`;
      });

      return ingredientIndexOffset + ingredientGroup.controls.ingredients.controls.length;
    }, 0);

    return ServerValidationHelper.setValidationErrors(errorResponse, this.form, fieldsMap);
  }

  private getIngredientInputNativeElement(
    groupIndex: number,
    ingredientIndex: number,
    field: string | null
  ): HTMLInputElement | null {
    const container = this.elementRef.nativeElement.querySelector(
      `[data-ingredient-key="${this.getIngredientKey(groupIndex, ingredientIndex)}"]`
    );

    if (field === null) {
      return container;
    }

    return container?.querySelector(`input[formControlName="${field}"]`);
  }
}

