import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';

@Component({
  selector: 'app-recipe-detail-preparation-content',
  templateUrl: './recipe-detail-preparation-content.component.html',
  styleUrls: ['./recipe-detail-preparation-content.component.scss'],
  standalone: true,
  imports: [CommonModule, SkeletonComponent, RepeatDirective],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailPreparationContentComponent {
  @Input() contentHtml!: string | null;
}
