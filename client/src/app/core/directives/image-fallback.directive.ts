import { Directive, ElementRef, HostListener, Input } from '@angular/core';

const DEFAULT_IMAGE = 'assets/images/image_placeholder.svg';

@Directive({
  selector: 'img[cbImageFallback]',
})
export class ImageFallbackDirective {
  @Input() cbImageFallback: string | null = null;

  constructor(private ref: ElementRef) {}

  @HostListener('error')
  loadError() {
    if (!this.cbImageFallback) {
      this.cbImageFallback = DEFAULT_IMAGE;
    }

    const element = this.ref.nativeElement;
    element.src = this.cbImageFallback;
  }
}
