import { Component, EventEmitter, Input, Output } from '@angular/core';
import { AbstractControl, FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';
import { Observable } from 'rxjs';
import { map, startWith } from 'rxjs/operators';
import { ApiService } from 'src/app/core/api/api.service';
import { EditIngredient, EditRecipe, ListIngredient, NewRecipe, RecipeFull } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { getFormError } from 'src/app/core/forms/Validation';
import { Logger, LoggerColor, trimAndNull } from 'src/app/core/functions';
import { TranslationService } from 'src/app/core/i18n/translation.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'cb-edit-recipe-information',
  templateUrl: './edit-recipe-information.component.html',
  styleUrls: ['./edit-recipe-information.component.scss'],
})
export class EditRecipeInformationComponent {
  @Input()
  set editRecipe(editRecipe: RecipeFull | null) {
    if (editRecipe) {
      let _editRecipe = editRecipe as any;

      _editRecipe.language = this.getLanguageInformation(_editRecipe.languageCode);

      this.recipeForm.reset(_editRecipe);
      this.ingredients.clear();
      this.ingredientIds = [];

      editRecipe.ingredients.forEach((ingredient) => {
        this.addIngredient(
          {
            amount: ingredient.amount,
            unit: ingredient.unit,
            name: ingredient.name,
            id: ingredient.id,
          },
          false
        );
      });
    }

    this._editRecipe = editRecipe;
  }

  @Input()
  set disabled(disabled: boolean) {
    if (!this.saving) {
      if (disabled) {
        this.recipeForm.disable();
      } else {
        this.recipeForm.enable();
      }
    }

    this._disabled = disabled;
  }

  get disabled() {
    return this._disabled;
  }

  @Output() saved = new EventEmitter<{ recipe: RecipeFull | null; ingredientsError: boolean }>();

  recipeForm: FormGroup;
  ingredients: FormArray;
  ingredientIds: (number | null)[] = [];

  focusedIngredient = -1;

  saving = false;
  error: string | null = null;
  errorDetails: string | null = null;
  ingredientsError = false; // used on edit

  ingredientList: ListIngredient[] | null = null;
  categoryList: string[] | null = null;

  filteredIngredientLists: Observable<ListIngredient[]>[] = [];
  filteredCategoryList: Observable<string[]> | null = null;

  private _disabled = false;

  private _editRecipe: RecipeFull | null = null;

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private snackbar: SnackbarService,
    public translation: TranslationService
  ) {
    this.ingredients = this.fb.array([]);

    this.recipeForm = this.fb.group({
      name: [this.editRecipe?.name || '', [Validators.required, Validators.maxLength(50)]],
      public: [this.editRecipe ? this.editRecipe.public : false, [Validators.required]],
      language: [
        this.getLanguageInformation(this.editRecipe?.languageCode || this.translation.language),
        [Validators.required],
      ],
      category: [this.editRecipe?.category || '', [Validators.maxLength(10)]],
      description: [this.editRecipe?.description || ''],
      portions: [this.editRecipe?.portions || null, [Validators.min(1)]],
      difficulty: [this.editRecipe?.difficulty || null, [Validators.min(0), Validators.max(5)]],
      preparation: [this.editRecipe?.preparation || ''],
      preparationTime: [this.editRecipe?.preparationTime || null, [Validators.min(1)]],
      restTime: [this.editRecipe?.restTime || null, [Validators.min(1)]],
      cookTime: [this.editRecipe?.cookTime || null, [Validators.min(1)]],
      ingredients: this.ingredients,
    });

    this.loadIngredientList();
    this.loadCategoryList();
  }

  get isEdit() {
    return this._editRecipe !== null;
  }

  get public() {
    return this.recipeForm.get('public');
  }
  get language() {
    return this.recipeForm.get('language');
  }
  get difficulty() {
    return this.recipeForm.get('difficulty');
  }

  getFormError(key: string, index: number | null = null) {
    let field: AbstractControl | null;

    if (index === null) {
      field = this.recipeForm?.get(key);
    } else {
      field = this.ingredients.controls[index].get(key);
    }

    return getFormError(field);
  }

  async loadIngredientList() {
    let res = await this.api.getIngredientsList();

    if (res.isOK()) {
      this.ingredientList = res.value;
    } else {
      Logger.error('EditRecipeInformation', LoggerColor.green, 'Error loading ingredients list:', res.error);
    }
  }

  async loadCategoryList() {
    let res = await this.api.getCategories();

    if (res.isOK() && res.value) {
      this.categoryList = res.value.map((categoryInfo) => categoryInfo.name);

      this.filteredCategoryList = this.recipeForm.get('category')!.valueChanges.pipe(
        startWith(''),
        map((value: string) => {
          const filterValue = value.toLowerCase();

          return this.categoryList?.filter((option) => option.toLowerCase().includes(filterValue)) || [];
        })
      );
    } else {
      Logger.error('EditRecipeInformation', LoggerColor.green, 'Error loading categories:', res.error);
    }
  }

  /**
   * Returns the value, if it is > 0 or "-" otherwise
   *
   * @param value The value between 0 and 5
   * @returns the value or "-"
   */
  difficultyValue(value: number) {
    if (value > 0) {
      return value;
    }

    return '-';
  }

  /**
   * Adds a new ingredient
   *
   * @param values The values to set or null if empty
   * @param focus Whether the input field should be focused
   */
  addIngredient(
    values: { amount: number | null; unit: string | null; name: string; id: number | null } | null = null,
    focus = true
  ) {
    this.ingredients.push(
      this.fb.group({
        amount: [values?.amount, [Validators.min(0.01)]],
        unit: [values?.unit, [Validators.maxLength(10)]],
        name: [values?.name, [Validators.maxLength(20), Validators.required]],
      })
    );
    this.ingredientIds.push(values ? values.id : null);

    const filteredIngredientList = this.ingredients
      .at(this.ingredients.length - 1)!
      .get('name')!
      .valueChanges.pipe(
        startWith(''),
        map((value: string | ListIngredient) => {
          const filterValue = value.toString().toLowerCase();

          return this.ingredientList?.filter((option) => option.name.toLowerCase().includes(filterValue)) || [];
        })
      );

    this.filteredIngredientLists.push(filteredIngredientList);

    if (focus) {
      this.focusedIngredient = this.ingredients.length - 1;
    }
  }

  /**
   * Removes the ingredient with the given index
   *
   * @param index the index of the ingredient
   */
  removeIngredient(index: number) {
    this.ingredients.removeAt(index);
    this.ingredientIds.splice(index, 1);
    this.filteredIngredientLists.splice(index, 1);
  }

  async onAutocompleteIngredientSelected(index: number, event: MatAutocompleteSelectedEvent) {
    let ingredient = event.option.value;

    this.ingredients.at(index).get('name')?.setValue(ingredient.name);
    this.ingredients.at(index).get('unit')?.setValue(ingredient.unit);
  }

  /**
   * Saves the recipe
   */
  async save() {
    if (this.recipeForm.invalid || (this.isEdit && !this._editRecipe)) return;

    this.saving = true;
    this.error = null;
    this.errorDetails = null;
    this.ingredientsError = false;
    this.recipeForm.disable();

    let values = this.recipeForm.value;
    let ingredients = values.ingredients;

    if (this.isEdit) {
      delete values.ingredients;
    }

    if (values.difficulty !== null) {
      values.difficulty++;
    }

    values.languageCode = values.language.key;
    delete values.language;

    let recipe: NewRecipe | EditRecipe = values;

    recipe.description = trimAndNull(recipe.description);
    recipe.category = trimAndNull(recipe.category);
    recipe.preparation = trimAndNull(recipe.preparation);

    if (recipe.difficulty === 0) {
      recipe.difficulty = null;
    } else if (typeof recipe.difficulty !== 'undefined' && recipe.difficulty !== null) {
      recipe.difficulty -= 1;
    }

    let res: ApiResponse<RecipeFull>;

    if (this._editRecipe) {
      if (this._editRecipe.name === recipe.name) {
        delete recipe.name;
      }
      if (this._editRecipe.public === recipe.public) {
        delete recipe.public;
      }
      if (this._editRecipe.languageCode === recipe.languageCode) {
        delete recipe.languageCode;
      }
      if (this._editRecipe.description === recipe.description) {
        delete recipe.description;
      }
      if (this._editRecipe.category === recipe.category) {
        delete recipe.category;
      }
      if (this._editRecipe.portions === recipe.portions) {
        delete recipe.portions;
      }
      if (this._editRecipe.difficulty === recipe.difficulty) {
        delete recipe.difficulty;
      }
      if (this._editRecipe.preparation === recipe.preparation) {
        delete recipe.preparation;
      }
      if (this._editRecipe.preparationTime === recipe.preparationTime) {
        delete recipe.preparationTime;
      }
      if (this._editRecipe.restTime === recipe.restTime) {
        delete recipe.restTime;
      }
      if (this._editRecipe.cookTime === recipe.cookTime) {
        delete recipe.cookTime;
      }

      // Updating ingredients

      const ingredientPromises: Promise<any>[] = [];

      this._editRecipe.ingredients.forEach((ingredient) => {
        let index = this.ingredientIds.indexOf(ingredient.id);

        if (index === -1) {
          ingredientPromises.push(this.api.deleteIngredient(ingredient.id));
        } else {
          let editIngredient: EditIngredient = ingredients[index];

          if (editIngredient.name) {
            editIngredient.name = editIngredient.name.trim();
          }
          editIngredient.unit = trimAndNull(editIngredient.unit);

          if (editIngredient.name === ingredient.name) {
            delete editIngredient.name;
          }
          if (editIngredient.amount === ingredient.amount) {
            delete editIngredient.amount;
          }
          if (editIngredient.unit === ingredient.unit) {
            delete editIngredient.unit;
          }

          if (Object.keys(editIngredient).length > 0) {
            ingredientPromises.push(this.api.editIngredient(ingredient.id, editIngredient));
          }
        }
      });

      this.ingredientIds.forEach((id, index) => {
        if (id === null && this._editRecipe) {
          ingredientPromises.push(this.api.addIngredient(this._editRecipe.id, ingredients[index]));
        }
      });

      const ingredientsResults: ApiResponse<any>[] = await Promise.all(ingredientPromises);

      this.ingredientsError = ingredientsResults.some((result) => !result.isOK());

      if (this.ingredientsError) {
        ingredientsResults.forEach((result) => {
          if (!result.isOK()) {
            Logger.error('EditRecipeInformation', LoggerColor.green, 'Error saving ingredient:', result.error);
          }
        });
      }

      // Updating recipe

      if (Object.keys(recipe).length > 0) {
        res = await this.api.editRecipe(this._editRecipe.id, <EditRecipe>recipe);
      } else {
        res = new ApiResponse<RecipeFull>(200, this._editRecipe);
      }
    } else {
      res = await this.api.createRecipe(<NewRecipe>recipe);
    }

    if (res.isOK()) {
      if (!this.isEdit) {
        history.pushState('', '', `/recipes/edit/${res.value?.id}`); // change url without redirect
      }

      this.editRecipe = res.value;

      this.saved.emit({
        recipe: res.value,
        ingredientsError: this.ingredientsError,
      });

      let saveMessage = 'messages.successfully_saved';

      if (this.ingredientsError) {
        saveMessage = 'messages.recipes.successfully_saved_ingredients_error';
      }

      this.snackbar.info(saveMessage);
    } else {
      this.error = 'messages.recipes.error_saving_recipe';

      if (res.error?.errorKey) {
        this.errorDetails = `api_error.${res.error.errorKey}`;
      }

      Logger.error('EditRecipeInformation', LoggerColor.green, 'Error saving recipe:', res.error);
    }

    this.saving = false;
    this.recipeForm.enable();
  }

  /**
   * Searches for the languages information in the availableLanguages
   *
   * @param language The language code to search
   * @returns The languages information of null if not found
   */
  getLanguageInformation(language: string | null) {
    if (language) {
      return this.translation.getLanguageInformation(language);
    }

    return null;
  }
}
