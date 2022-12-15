import { DialogRef, DIALOG_DATA } from '@angular/cdk/dialog';
import { AfterViewInit, ChangeDetectionStrategy, Component, ElementRef, Inject, ViewChild } from '@angular/core';

const SWIPE_LEFT_DIRECTION = 2;
const SWIPE_RIGHT_DIRECTION = 4;

@Component({
  selector: 'app-image-slider-dialog',
  templateUrl: './image-slider-dialog.component.html',
  styleUrls: ['./image-slider-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ImageSliderDialogComponent implements AfterViewInit {
  @ViewChild('imagesContainer') imagesContainer!: ElementRef<HTMLDivElement>;

  currentIndex: number;

  constructor(
    @Inject(DIALOG_DATA) private data: { images: string[]; startIndex?: number },
    public dialogRef: DialogRef<ImageSliderDialogComponent>
  ) {
    this.currentIndex = data.startIndex || 0;
    this.dialogRef.updateSize('100vw', '100vh');
  }

  get images() {
    return this.data.images;
  }

  ngAfterViewInit() {
    this.updateImagePosition();
  }

  nextImage(amount: number) {
    this.currentIndex += amount;

    if (this.currentIndex >= this.images.length) {
      this.currentIndex = 0;
    } else if (this.currentIndex < 0) {
      this.currentIndex = this.images.length - 1;
    }

    this.updateImagePosition();
  }

  onSwipe(e: any) {
    const direction = e.direction;

    if (direction === SWIPE_LEFT_DIRECTION) {
      this.nextImage(1);
    } else if (direction === SWIPE_RIGHT_DIRECTION) {
      this.nextImage(-1);
    }
  }

  private updateImagePosition() {
    if (this.imagesContainer.nativeElement.firstChild) {
      const firstImage = this.imagesContainer.nativeElement.firstChild as HTMLImageElement;

      firstImage.style.marginLeft = -this.currentIndex * 100 + '%';
    }
  }
}

