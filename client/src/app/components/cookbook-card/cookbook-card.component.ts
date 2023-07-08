import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { CookbookWithCounts } from 'src/app/core/models/cookbook';
import { SkeletonComponent } from '../skeleton/skeleton.component';

@Component({
  selector: 'app-cookbook-card',
  templateUrl: './cookbook-card.component.html',
  styleUrls: ['./cookbook-card.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterLink, MatIconModule, SkeletonComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookCardComponent {
  @Input() cookbook!: CookbookWithCounts | null;

  constructor() {}
}
