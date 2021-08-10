import { Directive, ElementRef, Input } from '@angular/core';

@Directive({
  selector: 'input[cbFocus]',
})
export class InputFocusDirective {
  @Input() cbFocus: boolean = true;

  constructor(private element: ElementRef<HTMLInputElement>) {}

  ngAfterViewInit(): void {
    if (this.cbFocus) {
      setTimeout(() => this.element.nativeElement.focus(), 0);
    }
  }
}
