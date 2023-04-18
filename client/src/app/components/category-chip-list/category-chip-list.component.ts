import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { ClampArrayPipe } from 'src/app/core/pipes/clamp-array.pipe';
import { SkeletonComponent } from '../skeleton/skeleton.component';

@Component({
  selector: 'app-category-chip-list',
  templateUrl: './category-chip-list.component.html',
  styleUrls: ['./category-chip-list.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterLink, ClampArrayPipe, SkeletonComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CategoryChipListComponent {
  @Input() categories!: string[] | null;
  @Input() routerLink: string = '/recipes';

  clampAmount = 5;

  skeletonChipWidths = Array.from({ length: 6 }, () => Math.floor(Math.random() * 5) + 5 + 'em');

  revealMoreCategories() {
    this.clampAmount += 5;
  }
}
