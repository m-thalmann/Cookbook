import { Component, Input } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from 'src/app/core/api/api.service';
import { RecipeImage } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-edit-recipe-images',
  templateUrl: './edit-recipe-images.component.html',
  styleUrls: ['./edit-recipe-images.component.scss'],
})
export class EditRecipeImagesComponent {
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

  recipeImages: RecipeImage[] | null = null;
  recipeImagesURLs: string[] | null = null;

  constructor(private api: ApiService, private snackBar: MatSnackBar) {}

  async reload() {
    if (this.recipeId === null) return;

    this.loading = true;

    let res = await this.api.getRecipeImages(this.recipeId);

    if (res.isOK()) {
      this.recipeImages = res.value;
      if (res.value) {
        this.recipeImagesURLs = res.value.map((image) => {
          return this.api.getRecipeImageURLById(image.id);
        });
      } else {
        this.recipeImagesURLs = [];
      }
    } else {
      console.error('Error loading recipe-images:', res.error);
      // TODO: error
    }

    this.loading = false;
  }

  async uploadImage(files: FileList | null) {
    if (this.recipeId === null || !files || files.length !== 1) {
      return;
    }

    this.saving = true;
    this.error = null;

    let res = await this.api.addRecipeImage(this.recipeId, files[0]);

    if (res.isOK()) {
      await this.reload();
      this.snackBar.open('Image was added successfully!', 'OK', {
        duration: 5000,
      });
    } else {
      this.error = res.error.info || 'Error uploading image';
      console.error('Error adding recipe-image:', res.error);
    }

    this.saving = false;
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
}
