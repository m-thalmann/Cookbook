import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-page-section',
  templateUrl: './page-section.component.html',
  styleUrls: ['./page-section.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PageSectionComponent {
  @Input() heading!: string;
  @Input() showMy = false;

  @Input() seeAllLink?: string | string[];

  constructor() {}
}
