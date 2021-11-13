import { Component, EventEmitter, Input, Output } from '@angular/core';
import { FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';
import { Observable } from 'rxjs';
import { filter, map, startWith } from 'rxjs/operators';
import { ApiService } from 'src/app/core/api/api.service';
import {
  EditIngredient,
  EditRecipe,
  ListIngredient,
  NewIngredient,
  NewRecipe,
  RecipeFull,
} from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { getFormError, minArrayLength } from 'src/app/core/forms/Validation';
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
      this.ingredientGroups.clear();
      this.ingredientIds = [];
      this.ingredientAmount = 0;

      editRecipe.ingredients.forEach((ingredientGroup, index) => {
        this.addIngredientGroup(ingredientGroup.group);

        ingredientGroup.items.forEach((ingredient) => {
          this.addIngredient(
            index,
            {
              amount: ingredient.amount,
              unit: ingredient.unit,
              name: ingredient.name,
              id: ingredient.id,
            },
            false
          );
        });
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
  ingredientGroups: FormArray;
  /**
   * Stores the id's of existing ingredients (values) for the ingredient-groups (key)
   */
  ingredientIds: (number | null)[][] = [];
  ingredientAmount = 0;

  focusedIngredient: string | null = null;

  saving = false;
  error: string | null = null;
  errorDetails: string | null = null;
  ingredientsError = false; // used on edit

  ingredientList: ListIngredient[] | null = null;
  categoryList: string[] | null = null;

  filteredCategoryList: Observable<string[]> | null = null;
  filteredIngredientLists: Observable<ListIngredient[]>[][] = [];

  private _disabled = false;

  private _editRecipe: RecipeFull | null = null;

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private snackbar: SnackbarService,
    public translation: TranslationService
  ) {
    this.ingredientGroups = this.fb.array([]);

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
      ingredientGroups: this.ingredientGroups,
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

  getFormError(key: string) {
    let field = this.recipeForm?.get(key);

    return getFormError(field);
  }

  getIngredientGroupFormError(key: string, groupIndex: number) {
    let field = this.ingredientGroups.controls[groupIndex].get(key);

    return getFormError(field);
  }

  getIngredientFormError(key: string, groupIndex: number, ingredientIndex: number) {
    let field = (this.ingredientGroups.controls[groupIndex].get('items') as FormArray).at(ingredientIndex).get(key);

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
        map((value: string | null) => {
          const filterValue = (value || '').toLowerCase();

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
   * Returns the index of the ingredient group or -1 if not found
   *
   * @param name The name of the group
   */
  getIngredientGroupIndex(name: string) {
    for (let i = 0; i < this.ingredientGroups.length; i++) {
      if (this.ingredientGroups.at(i).get('name')?.value === name) return i;
    }

    return -1;
  }

  /**
   * Returns the ingredients-group ingredients-array
   *
   * @param groupIndex The index of the group
   *
   * @returns the ingredients-array for the group
   */
  getIngredientGroupIngredients(groupIndex: number): FormArray | null {
    return this.ingredientGroups.at(groupIndex).get('items') as FormArray;
  }

  /**
   * Adds a new ingredient-group
   *
   * @param name The name of the group
   * @param addIngredient Whether to add an empty ingredient or not
   * @param focusIngredient Whether to focus the added ingredient or not
   */
  addIngredientGroup(name: string | null, addIngredient = false, focusIngredient = false) {
    if (name !== null && this.getIngredientGroupIndex(name) !== -1) return;

    this.ingredientGroups.push(
      this.fb.group({
        name: [name, Validators.maxLength(20)],
        items: this.fb.array([], minArrayLength(1)),
      })
    );
    this.ingredientIds.push([]);
    this.filteredIngredientLists.push([]);

    if (addIngredient) {
      this.addIngredient(this.ingredientGroups.length - 1, null, focusIngredient);
    }
  }

  /**
   * Adds a new ingredient
   *
   * @param groupIndex The index of the group
   * @param values The values to set or null if empty
   * @param focus Whether the input field should be focused
   */
  addIngredient(
    groupIndex: number,
    values: {
      amount: number | null;
      unit: string | null;
      name: string;
      id: number | null;
    } | null = null,
    focus = true
  ) {
    let ingredients = this.ingredientGroups.get([groupIndex, 'items']) as FormArray;

    ingredients.push(
      this.fb.group({
        amount: [values?.amount, [Validators.min(0.01)]],
        unit: [values?.unit, [Validators.maxLength(20)]],
        name: [values?.name, [Validators.maxLength(40), Validators.required]],
      })
    );

    this.ingredientIds[groupIndex].push(values ? values.id : null);
    this.ingredientAmount++;

    let ingredientIndex = ingredients.length - 1;

    const filteredIngredientList = ingredients.get([ingredients.length - 1, 'name'])!.valueChanges.pipe(
      startWith(''),
      // on autocomplete the value will be set to a ListIngredient (directly overwritten by onAutocompleteIngredientSelected(...)-function)
      filter((value) => typeof value !== 'object'),
      map((value: string | null) => {
        const filterValue = (value || '').toLowerCase();

        return this.ingredientList?.filter((option) => option.name.toLowerCase().includes(filterValue)) || [];
      })
    );

    this.filteredIngredientLists[groupIndex].push(filteredIngredientList);

    if (focus) {
      this.focusedIngredient = `${groupIndex}-${ingredientIndex}`;
    } else {
      this.focusedIngredient = null;
    }
  }

  /**
   * Removes the ingredient-group with the given name
   *
   * @param index The index of the group
   */
  removeIngredientGroup(index: number) {
    this.ingredientGroups.removeAt(index);

    this.ingredientAmount -= this.ingredientIds[index].length;
    this.ingredientIds.splice(index, 1);
    this.filteredIngredientLists.splice(index, 1);
  }

  /**
   * Removes the ingredient with the given index
   *
   * @param groupIndex The index of the group
   * @param index the index of the ingredient
   */
  removeIngredient(groupIndex: number, index: number) {
    let ingredients = this.ingredientGroups.at(groupIndex).get('items') as FormArray;

    ingredients.removeAt(index);

    this.ingredientIds[groupIndex].splice(index, 1);
    this.ingredientAmount--;
    this.filteredIngredientLists[groupIndex].splice(index, 1);
  }

  async onAutocompleteIngredientSelected(groupIndex: number, index: number, event: MatAutocompleteSelectedEvent) {
    let ingredients = this.getIngredientGroupIngredients(groupIndex);

    if (!ingredients) {
      return;
    }

    let ingredient = event.option.value;

    ingredients.at(index).get('name')?.setValue(ingredient.name);
    ingredients.at(index).get('unit')?.setValue(ingredient.unit);
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
    let ingredientGroups: {
      name: string | null;
      items: { name: string; unit: string | null; amount: number | null }[];
    }[] = values.ingredientGroups;

    delete values.ingredientGroups;

    let ingredients: NewIngredient[] = [];
    let ingredientIds: (number | null)[] = [];

    ingredientGroups.forEach((group, groupIndex) => {
      if (group.name === null) {
        group.name = '';
      }
      group.name = group.name.trim();

      group.items.forEach((ingredient, index) => {
        ingredients.push({
          name: ingredient.name.trim(),
          unit: trimAndNull(ingredient.unit),
          amount: ingredient.amount,
          group: group.name || '',
        });
        ingredientIds.push(this.ingredientIds[groupIndex][index]);
      });
    });

    if (!this.isEdit) {
      values.ingredients = ingredients;
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

      this._editRecipe.ingredients.forEach((group) => {
        group.items.forEach((ingredient) => {
          let index = ingredientIds.indexOf(ingredient.id);

          if (index === -1) {
            ingredientPromises.push(this.api.deleteIngredient(ingredient.id));
          } else {
            let newIngredient: NewIngredient = ingredients[index];
            let editIngredient: EditIngredient = {};

            if (newIngredient.name) {
              newIngredient.name = newIngredient.name.trim();
            }
            newIngredient.unit = trimAndNull(newIngredient.unit);
            newIngredient.group = newIngredient.group?.trim() || '';

            if (newIngredient.name !== ingredient.name) {
              editIngredient.name = newIngredient.name;
            }
            if (newIngredient.amount !== ingredient.amount) {
              editIngredient.amount = newIngredient.amount;
            }
            if (newIngredient.unit !== ingredient.unit) {
              editIngredient.unit = newIngredient.unit;
            }
            if (newIngredient.group !== ingredient.group) {
              editIngredient.group = newIngredient.group;
            }

            if (Object.keys(editIngredient).length > 0) {
              ingredientPromises.push(this.api.editIngredient(ingredient.id, editIngredient));
            }
          }
        });
      });

      ingredientIds.forEach((id, index) => {
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
        res = await this.api.getRecipeById(this._editRecipe.id);
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
