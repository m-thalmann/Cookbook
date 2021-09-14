import { Component, Input, OnDestroy } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeImage } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { SubSink } from 'src/app/core/functions';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

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

  private subSink = new SubSink();

  constructor(private api: ApiService, private snackbar: SnackbarService) {}

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
      this.error = 'messages.recipe_images.error_loading_recipe_images';
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

    this.subSink.push(
      this.api.addRecipeImage(this.recipeId, files[0]).subscribe(async (event) => {
        if (event instanceof ApiResponse) {
          if (event.isOK()) {
            await this.reload();
            this.snackbar.info('messages.recipe_images.image_added_successfully');
          } else {
            this.error = event.error.info || 'messages.recipe_images.error_uploading_image';
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
        this.snackbar.info('messages.recipe_images.image_deleted_successfully');
      } else {
        this.snackbar.error('messages.recipe_images.error_deleting_image');
        console.error('Error deleting recipe-image:', res.error);
      }

      this.saving = false;
    }
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
