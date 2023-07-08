import { ClipboardModule } from '@angular/cdk/clipboard';
import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, ChangeDetectorRef, Component, Inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { MatSlideToggleChange, MatSlideToggleModule } from '@angular/material/slide-toggle';
import { Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { ApiService } from 'src/app/core/api/api.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-recipe-public-share-dialog',
  templateUrl: './recipe-public-share-dialog.component.html',
  styleUrls: ['./recipe-public-share-dialog.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    MatSlideToggleModule,
    MatDialogModule,
    MatProgressSpinnerModule,
    ClipboardModule,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipePublicShareDialogComponent {
  recipe: DetailedRecipe;
  publicShareEnabled: boolean;

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
    this.publicShareEnabled = this.recipe.share_uuid !== null;
  }

  get shareLink() {
    if (!this.recipe.share_uuid) return '';

    let baseUri = document.baseURI;

    if (baseUri.endsWith('/')) {
      baseUri = baseUri.substring(0, baseUri.length - 1);
    }

    return baseUri + this.router.createUrlTree(['/recipes/shared', this.recipe.share_uuid]).toString();
  }

  async enabledChanged(e: MatSlideToggleChange) {
    this.updating = true;
    this.dialogRef.disableClose = true;

    this.publicShareEnabled = e.checked;

    try {
      const response = await toPromise(this.api.recipes.update(this.recipe.id, { is_shared: this.publicShareEnabled }));

      // recipe is passed by reference, so this is also updated in caller
      this.recipe.share_uuid = response.body!.data.share_uuid;
    } catch (e) {
      this.publicShareEnabled = !this.publicShareEnabled;

      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error updating recipe share-uuid:', errorMessage, e);
    }

    this.updating = false;
    this.dialogRef.disableClose = false;

    this.changeDetector.markForCheck();
  }

  linkCopied() {
    this.snackbar.info('messages.linkCopied', { translateMessage: true });
  }
}
