import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';

@Component({
  selector: 'app-page-section',
  templateUrl: './page-section.component.html',
  styleUrls: ['./page-section.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterLink, TranslocoModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class PageSectionComponent {
  @Input() heading!: string;
  @Input() showMy = false;

  @Input() seeAllLink?: string | string[];

  constructor() {}
}
