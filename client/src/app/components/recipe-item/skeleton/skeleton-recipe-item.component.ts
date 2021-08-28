import { Component, Input } from '@angular/core';

@Component({
  selector: 'cb-skeleton-recipe-item',
  templateUrl: './skeleton-recipe-item.component.html',
  styleUrls: ['./skeleton-recipe-item.component.scss'],
})
export class SkeletonRecipeItemComponent {
  @Input() compact = false;

  constructor() {}
}
