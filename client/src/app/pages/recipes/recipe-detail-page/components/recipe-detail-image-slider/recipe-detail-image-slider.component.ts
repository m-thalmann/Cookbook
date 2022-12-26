import { Dialog } from '@angular/cdk/dialog';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { ImageSliderDialogComponent } from 'src/app/components/image-slider-dialog/image-slider-dialog.component';
import { RecipeImage } from 'src/app/core/models/recipe-image';

const SWIPE_LEFT_DIRECTION = 2;
const SWIPE_RIGHT_DIRECTION = 4;

@Component({
  selector: 'app-recipe-detail-image-slider',
  templateUrl: './recipe-detail-image-slider.component.html',
  styleUrls: ['./recipe-detail-image-slider.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailImageSliderComponent {
  @Input()
  set images(images: RecipeImage[]) {
    this._images = images;
    this.currentImageIndex = 0;
  }

  private _images!: RecipeImage[];

  currentImageIndex = 0;

  constructor(private dialog: Dialog) {}

  get images() {
    return this._images;
  }

  nextImage(amount: number) {
    this.currentImageIndex += amount;

    if (this.currentImageIndex >= this.images.length) {
      this.currentImageIndex = 0;
    } else if (this.currentImageIndex < 0) {
      this.currentImageIndex = this.images.length - 1;
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
    this.dialog.open(ImageSliderDialogComponent, {
      data: { images: this.images.map((image) => image.url), startIndex: this.currentImageIndex },
    });
  }
}
