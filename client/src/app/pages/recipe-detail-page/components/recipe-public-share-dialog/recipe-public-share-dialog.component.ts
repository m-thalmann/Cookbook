import { ChangeDetectionStrategy, ChangeDetectorRef, Component, ElementRef, Inject, ViewChild } from '@angular/core';
import { MatDialogRef, MAT_DIALOG_DATA } from '@angular/material/dialog';
import { MatSlideToggleChange } from '@angular/material/slide-toggle';
import { Router } from '@angular/router';
import { lastValueFrom } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-recipe-public-share-dialog',
  templateUrl: './recipe-public-share-dialog.component.html',
  styleUrls: ['./recipe-public-share-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipePublicShareDialogComponent {
  recipe: DetailedRecipe;

  updating = false;

  constructor(
    @Inject(MAT_DIALOG_DATA) public data: { recipe: DetailedRecipe },
    private dialogRef: MatDialogRef<RecipePublicShareDialogComponent>,
    private snackbar: SnackbarService,
    private api: ApiService,
    private router: Router,
    private changeDetector: ChangeDetectorRef
  ) {
    this.recipe = data.recipe;
  }

  get shareLink() {
    if (!this.recipe.share_uuid) return '';

    let baseUri = document.baseURI;

    if (baseUri.endsWith('/')) {
      baseUri = baseUri.substring(0, baseUri.length - 1);
    }

    return baseUri + this.router.createUrlTree(['/recipe/shared', this.recipe.share_uuid]).toString();
  }

  async enabledChanged(e: MatSlideToggleChange) {
    this.updating = true;
    this.dialogRef.disableClose = true;

    const enabled = e.checked;

    try {
      const response = await lastValueFrom(this.api.recipes.update(this.recipe.id, { is_shared: enabled }));

      // recipe is passed by reference, so this is also updated in caller
      this.recipe.share_uuid = response.body!.data.share_uuid;
    } catch (e) {
      Logger.error('Error updating recipe share-uuid:', e);
    }

    this.updating = false;
    this.dialogRef.disableClose = false;

    this.changeDetector.markForCheck();
  }

  linkCopied() {
    this.snackbar.info('Link copied');
  }
}

