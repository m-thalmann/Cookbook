import { Dialog } from '@angular/cdk/dialog';
import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ImageSliderDialogComponent } from 'src/app/components/image-slider-dialog/image-slider-dialog.component';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { PLACEHOLDER_RECIPE_IMAGE_URL, RecipeImage } from 'src/app/core/models/recipe-image';

const SWIPE_LEFT_DIRECTION = 2;
const SWIPE_RIGHT_DIRECTION = 4;

@Component({
  selector: 'app-recipe-detail-image-slider',
  templateUrl: './recipe-detail-image-slider.component.html',
  styleUrls: ['./recipe-detail-image-slider.component.scss'],
  standalone: true,
  imports: [CommonModule, MatButtonModule, MatIconModule, SkeletonComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailImageSliderComponent {
  @Input()
  set images(images: RecipeImage[] | null) {
    this._images = images;
    this.currentImageIndex = 0;
  }

  @Input()
  @CoerceBooleanProperty()
  disabled: any = false;

  private _images!: RecipeImage[] | null;

  currentImageIndex = 0;

  constructor(private cdkDialog: Dialog) {}

  get images() {
    return this._images;
  }

  get currentImageUrl() {
    if (this.images!.length === 0) {
      return PLACEHOLDER_RECIPE_IMAGE_URL;
    }

    return this.images![this.currentImageIndex].url;
  }

  nextImage(amount: number) {
    this.currentImageIndex += amount;

    if (this.currentImageIndex >= this.images!.length) {
      this.currentImageIndex = 0;
    } else if (this.currentImageIndex < 0) {
      this.currentImageIndex = this.images!.length - 1;
    }
  }

  onSwipe(e: any) {
    const direction = e.direction;

    if (direction === SWIPE_LEFT_DIRECTION) {
      this.nextImage(1);
    } else if (direction === SWIPE_RIGHT_DIRECTION) {
      this.nextImage(-1);
    }
  }

  openDialog() {
    const imageUrls = this.images!.map((image) => image.url);

    if (imageUrls.length === 0) {
      imageUrls.push(PLACEHOLDER_RECIPE_IMAGE_URL);
    }

    this.cdkDialog.open(ImageSliderDialogComponent, {
      data: { images: imageUrls, startIndex: this.currentImageIndex },
    });
  }
}
