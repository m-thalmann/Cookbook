import {AfterViewInit, Directive, ElementRef, Input} from '@angular/core';

@Directive({
  selector: 'input[cbFocus]',
})
export class InputFocusDirective implements AfterViewInit{
  @Input() cbFocus: boolean = true;

  constructor(private element: ElementRef<HTMLInputElement>) {}

  ngAfterViewInit(): void {
    if (this.cbFocus) {
      setTimeout(() => this.element.nativeElement.focus(), 0);
    }
  }
}
