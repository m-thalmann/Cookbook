import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { TranslocoModule } from '@ngneat/transloco';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { DetailedRecipe } from 'src/app/core/models/recipe';

@Component({
  selector: 'app-recipe-detail-preparation-content',
  templateUrl: './recipe-detail-preparation-content.component.html',
  styleUrls: ['./recipe-detail-preparation-content.component.scss'],
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, SkeletonComponent, RepeatDirective],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailPreparationContentComponent {
  @Input() recipe!: DetailedRecipe | null;
}
