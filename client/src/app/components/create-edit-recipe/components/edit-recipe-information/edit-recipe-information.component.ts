import { Component, EventEmitter, Input, Output } from '@angular/core';
import { AbstractControl, FormArray, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatSnackBar } from '@angular/material/snack-bar';
import { debounceTime, distinctUntilChanged, filter, switchMap } from 'rxjs/operators';
import { ApiService } from 'src/app/core/api/api.service';
import { EditIngredient, EditRecipe, NewRecipe, RecipeFull, SearchIngredient } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { getFormError } from 'src/app/core/forms/Validation';
import { trimAndNull } from 'src/app/core/functions';

@Component({
  selector: 'cb-edit-recipe-information',
  templateUrl: './edit-recipe-information.component.html',
  styleUrls: ['./edit-recipe-information.component.scss'],
})
export class EditRecipeInformationComponent {
  @Input()
  set editRecipe(editRecipe: RecipeFull | null) {
    if (editRecipe) {
      this.recipeForm.reset(editRecipe);
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
  set disabled(disabled: boolean){
    if(!this.saving){
      if(disabled){
        this.recipeForm.disable();
      }else{
        this.recipeForm.enable();
      }
    }

    this._disabled = disabled;
  }

  get disabled(){
    return this._disabled;
  }

  @Output() saved = new EventEmitter<RecipeFull | null>();

  recipeForm: FormGroup;
  ingredients: FormArray;
  ingredientIds: (number | null)[] = [];

  focusedIngredient = -1;

  saving = false;
  error: string | null = null;

  private _disabled = false;

  private _editRecipe: RecipeFull | null = null;

  constructor(private fb: FormBuilder, private api: ApiService, private snackBar: MatSnackBar) {
    this.ingredients = this.fb.array([]);

    this.recipeForm = this.fb.group({
      name: [this.editRecipe?.name || '', [Validators.required, Validators.maxLength(20)]],
      public: [this.editRecipe ? this.editRecipe.public : false, [Validators.required]],
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
  }

  get isEdit() {
    return this._editRecipe !== null;
  }

  get public() {
    return this.recipeForm?.get('public');
  }
  get difficulty() {
    return this.recipeForm?.get('difficulty');
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
  }

  /**
   * Saves the recipe
   */
  async save() {
    if (this.recipeForm.invalid || (this.isEdit && !this._editRecipe)) return;

    this.saving = true;
    this.error = null;
    this.recipeForm.disable();

    let values = this.recipeForm.value;
    let ingredients = values.ingredients;

    if (this.isEdit) {
      delete values.ingredients;
    }

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

      await Promise.all(ingredientPromises);

      // Updating recipe

      res = await this.api.editRecipe(this._editRecipe.id, <EditRecipe>recipe);
    } else {
      res = await this.api.createRecipe(<NewRecipe>recipe);
    }

    if (res.isOK()) {
      if (!this.isEdit) {
        history.pushState('', '', `/edit/${res.value?.id}`); // change url without redirect
      }

      this.saved.emit(res.value);

      this.snackBar.open('Successfully saved!', 'OK');
    } else {
      this.error = 'Error saving recipe';

      if (res.error?.info) {
        this.error += `: ${res.error.info}`;
      }
    }

    this.saving = false;
    this.recipeForm.enable();
  }
}
