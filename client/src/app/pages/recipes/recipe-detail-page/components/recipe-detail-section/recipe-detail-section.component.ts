import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-recipe-detail-section',
  templateUrl: './recipe-detail-section.component.html',
  styleUrls: ['./recipe-detail-section.component.scss'],
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailSectionComponent {
  @Input() hideHeader = false;
}
