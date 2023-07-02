import { Directive, Input, TemplateRef, ViewContainerRef } from '@angular/core';

@Directive({
  selector: '[appRepeat]',
  standalone: true,
})
export class RepeatDirective {
  constructor(private templateRef: TemplateRef<any>, private viewContainer: ViewContainerRef) {}

  @Input('appRepeat') set count(count: number) {
    this.viewContainer.clear();
    for (let i = 0; i < count; i++) {
      this.viewContainer.createEmbeddedView(this.templateRef);
    }
  }
}
