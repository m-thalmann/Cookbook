import { Component, Input, ViewChild } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatStepper } from '@angular/material/stepper';
import { Router } from '@angular/router';
import { ConfirmDialogComponent } from 'src/app/components/confirm-dialog/confirm-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeFull } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';
import { Logger, LoggerColor, slugify } from 'src/app/core/functions';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

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
    private snackbar: SnackbarService,
    private router: Router,
    public user: UserService
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
          translate: true,
          translationKey: 'dialogs.delete_recipe',
          warn: true,
        },
      })
      .afterClosed()
      .toPromise();

    if (!doDelete) return;

    this.disabled = true;

    let res = await this.api.deleteRecipe(this.editRecipe.id);

    if (res.isOK()) {
      this.snackbar.info('messages.recipes.recipe_deleted_successfully');
      await this.router.navigateByUrl('/home');
    } else {
      this.snackbar.error('messages.recipes.error_deleting_recipe');
      Logger.error('CreateEditRecipe', LoggerColor.green, 'Error deleting recipe:', res.error);
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
