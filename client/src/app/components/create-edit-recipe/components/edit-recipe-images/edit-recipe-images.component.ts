import { Component, Input, OnDestroy } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeImage } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';

@Component({
  selector: 'cb-edit-recipe-images',
  templateUrl: './edit-recipe-images.component.html',
  styleUrls: ['./edit-recipe-images.component.scss'],
})
export class EditRecipeImagesComponent implements OnDestroy {
  @Input()
  set recipeId(recipeId: number | null) {
    this._recipeId = recipeId;

    if (recipeId !== null) {
      this.reload();
    }
  }
  get recipeId() {
    return this._recipeId;
  }

  @Input() disabled: boolean = false;

  private _recipeId: number | null = null;

  loading = false;
  saving = false;
  error: string | null = null;

  uploadProgress: number | null = null;

  recipeImages: RecipeImage[] | null = null;
  recipeImagesURLs: string[] | null = null;

  private subscriptions: Subscription[] = [];

  constructor(private api: ApiService, private snackBar: MatSnackBar) {}

  async reload() {
    if (this.recipeId === null) return;

    this.loading = true;

    let res = await this.api.getRecipeImages(this.recipeId);

    if (res.isOK()) {
      this.recipeImages = res.value;
      if (res.value) {
        this.recipeImagesURLs = res.value.map((image) => {
          return this.api.getRecipeImageURLById(image.id, 250);
        });
      } else {
        this.recipeImagesURLs = [];
      }
    } else {
      console.error('Error loading recipe-images:', res.error);
      this.error = 'Error loading recipe-images';
    }

    this.loading = false;
  }

  async uploadImage(files: FileList | null) {
    if (this.recipeId === null || !files || files.length !== 1) {
      return;
    }

    this.saving = true;
    this.error = null;
    this.uploadProgress = 0;

    this.subscriptions.push(
      this.api.addRecipeImage(this.recipeId, files[0]).subscribe(async (event) => {
        if (event instanceof ApiResponse) {
          if (event.isOK()) {
            await this.reload();
            this.snackBar.open('Image was added successfully!', 'OK', {
              duration: 5000,
            });
          } else {
            this.error = event.error.info || 'Error uploading image';
            console.error('Error adding recipe-image:', event.error);
          }

          this.saving = false;
          this.uploadProgress = null;
        } else if (typeof event === 'number') {
          this.uploadProgress = event;
        }
      })
    );
  }

  async deleteImage(index: number) {
    if (this.recipeImages && this.recipeImages[index]) {
      this.saving = true;

      let res = await this.api.deleteRecipeImage(this.recipeImages[index].id);

      if (res.isOK()) {
        await this.reload();
        this.snackBar.open('Image was deleted successfully!', 'OK', {
          duration: 5000,
        });
      } else {
        this.snackBar.open('Image could not be deleted!', 'OK', {
          panelClass: 'action-warn',
        });
        console.error('Error deleting recipe-image:', res.error);
      }

      this.saving = false;
    }
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
