import { Component, Input } from '@angular/core';

const DEFAULT_IMAGE = 'assets/images/image_placeholder.svg';

@Component({
  selector: 'cb-image',
  templateUrl: './image.component.html',
  styleUrls: ['./image.component.scss'],
})
export class ImageComponent {
  @Input() src!: string;
  @Input() alt: string = '';

  @Input() fallback: string = DEFAULT_IMAGE;

  loading = true;

  constructor() {}

  onLoad() {
    this.loading = false;
  }

  onError() {
    this.src = this.fallback;
  }
}
