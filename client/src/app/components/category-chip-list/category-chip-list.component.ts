import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-category-chip-list',
  templateUrl: './category-chip-list.component.html',
  styleUrls: ['./category-chip-list.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CategoryChipListComponent {
  @Input() categories!: string[];
  @Input() routerLink: string = '/recipes';

  clampAmount = 5;

  revealMoreCategories() {
    this.clampAmount += 5;
  }
}

