import { DIALOG_DATA, DialogRef } from '@angular/cdk/dialog';
import { CommonModule } from '@angular/common';
import { AfterViewInit, ChangeDetectionStrategy, Component, ElementRef, Inject, ViewChild } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';

const SWIPE_LEFT_DIRECTION = 2;
const SWIPE_RIGHT_DIRECTION = 4;
const SWIPE_UP_DIRECTION = 8;
const SWIPE_DOWN_DIRECTION = 16;

@Component({
  selector: 'app-image-slider-dialog',
  templateUrl: './image-slider-dialog.component.html',
  styleUrls: ['./image-slider-dialog.component.scss'],
  standalone: true,
  imports: [CommonModule, MatDialogModule, MatIconModule, MatButtonModule],
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

    switch (direction) {
      case SWIPE_LEFT_DIRECTION:
        this.nextImage(1);
        break;
      case SWIPE_RIGHT_DIRECTION:
        this.nextImage(-1);
        break;
      case SWIPE_UP_DIRECTION:
      case SWIPE_DOWN_DIRECTION:
        this.dialogRef.close();
        break;
    }
  }

  onKeyUp(e: KeyboardEvent) {
    switch (e.key) {
      case 'ArrowLeft':
        this.nextImage(-1);
        break;
      case 'ArrowRight':
        this.nextImage(1);
        break;
    }
  }

  private updateImagePosition() {
    if (this.imagesContainer.nativeElement.firstChild) {
      const firstImage = this.imagesContainer.nativeElement.firstChild as HTMLImageElement;

      firstImage.style.marginLeft = -this.currentIndex * 100 + '%';
    }
  }
}
