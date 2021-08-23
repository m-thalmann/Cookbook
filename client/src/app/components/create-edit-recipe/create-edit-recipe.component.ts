import { Component, Input, ViewChild } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatStepper } from '@angular/material/stepper';
import { Router } from '@angular/router';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeFull } from 'src/app/core/api/ApiInterfaces';
import { slugify } from 'src/app/core/functions';
import { ConfirmDialogComponent } from '../confirm-dialog/confirm-dialog.component';

@Component({
  selector: 'cb-create-edit-recipe',
  templateUrl: './create-edit-recipe.component.html',
  styleUrls: ['./create-edit-recipe.component.scss'],
})
export class CreateEditRecipeComponent {
  @ViewChild('stepper') stepper!: MatStepper;

  @Input() editRecipe: RecipeFull | null = null;

  disabled = false;

  constructor(
    private api: ApiService,
    private dialog: MatDialog,
    private snackBar: MatSnackBar,
    private router: Router
  ) {}

  get isEdit() {
    return this.editRecipe !== null;
  }

  get recipeURL() {
    if (!this.editRecipe) return;

    return `/recipes/${this.editRecipe.id}/${slugify(this.editRecipe.name)}`;
  }

  async deleteRecipe() {
    if (!this.editRecipe) return;

    let doDelete = await this.dialog
      .open(ConfirmDialogComponent, {
        data: {
          title: 'Delete recipe?',
          content: 'Are you sure you want to delete this recipe? This action is not reversible',
          warn: true,
        },
      })
      .afterClosed()
      .toPromise();

    if (!doDelete) return;

    this.disabled = true;

    let res = await this.api.deleteRecipe(this.editRecipe.id);

    if (res.isOK()) {
      this.snackBar.open('Recipe deleted successfully!', 'OK', {
        duration: 5000,
      });
      await this.router.navigateByUrl('/home');
    } else {
      this.snackBar.open('Error deleting recipe!', 'OK', {
        panelClass: 'action-warn',
      });
      console.error('Error deleting recipe:', res.error);
    }

    this.disabled = false;
  }

  informationSaved(save: { recipe: RecipeFull | null; ingredientsError: boolean }) {
    this.editRecipe = save.recipe;

    if (!save.ingredientsError) {
      setTimeout(() => {
        this.stepper.next();
      }, 0);
    }
  }
}
