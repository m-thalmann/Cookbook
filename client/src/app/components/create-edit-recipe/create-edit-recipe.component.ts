import { Component, Input, ViewChild } from '@angular/core';
import { MatStepper } from '@angular/material/stepper';
import { RecipeFull } from 'src/app/core/api/ApiInterfaces';

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

  informationSaved(recipe: RecipeFull | null) {
    this.editRecipe = recipe;

    setTimeout(() => {
      this.stepper.next();
    }, 0);
  }
}
