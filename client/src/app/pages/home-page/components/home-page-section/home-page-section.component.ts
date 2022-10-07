import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-home-page-section',
  templateUrl: './home-page-section.component.html',
  styleUrls: ['./home-page-section.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class HomePageSectionComponent {
  @Input() title!: string;
  @Input() showTitleMy = false;

  @Input() seeAllLink?: string | string[];

  constructor() {}
}

