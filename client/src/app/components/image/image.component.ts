import { Component, Input } from '@angular/core';

const DEFAULT_IMAGE = 'assets/images/image_placeholder.svg';

@Component({
  selector: 'cb-image',
  templateUrl: './image.component.html',
  styleUrls: ['./image.component.scss'],
})
export class ImageComponent {
  @Input()
  set src(src: string) {
    if (this._src !== src) {
      this.loading = true;
    }

    this._src = src;
  }

  @Input() alt: string = '';

  @Input() fallback: string = DEFAULT_IMAGE;

  private _src: string = '';

  loading = true;

  constructor() {}

  get src() {
    return this._src;
  }

  onLoad() {
    this.loading = false;
  }

  onError() {
    this.src = this.fallback;
  }
}
