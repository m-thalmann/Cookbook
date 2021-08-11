import { Component, Input, ViewChild } from '@angular/core';
import { MatStepper } from '@angular/material/stepper';
import { RecipeFull } from 'src/app/core/api/ApiInterfaces';
import { slugify } from 'src/app/core/functions';

@Component({
  selector: 'cb-create-edit-recipe',
  templateUrl: './create-edit-recipe.component.html',
  styleUrls: ['./create-edit-recipe.component.scss'],
})
export class CreateEditRecipeComponent {
  @ViewChild('stepper') stepper!: MatStepper;

  @Input() editRecipe: RecipeFull | null = null;

  constructor() {}

  get isEdit() {
    return this.editRecipe !== null;
  }

  get recipeURL() {
    if (!this.editRecipe) return;

    return `/recipes/${this.editRecipe.id}/${slugify(this.editRecipe.name)}`;
  }

  deleteRecipe() {
    // TODO:
  }

  informationSaved(recipe: RecipeFull | null) {
    this.editRecipe = recipe;

    setTimeout(() => {
      this.stepper.next();
    }, 0);
  }
}
