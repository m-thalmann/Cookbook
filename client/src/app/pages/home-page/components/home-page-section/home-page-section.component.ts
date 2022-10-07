import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-home-page-section',
  templateUrl: './home-page-section.component.html',
  styleUrls: ['./home-page-section.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class HomePageSectionComponent {
  @Input() heading!: string;
  @Input() showMy = false;

  @Input() seeAllLink?: string | string[];

  constructor() {}
}

