import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ClampArrayPipe } from 'src/app/core/pipes/clamp-array.pipe';

@Component({
  selector: 'app-category-chip-list',
  templateUrl: './category-chip-list.component.html',
  styleUrls: ['./category-chip-list.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterLink, ClampArrayPipe],
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
