import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

@Component({
  selector: 'app-recipe-detail-preparation-content',
  templateUrl: './recipe-detail-preparation-content.component.html',
  styleUrls: ['./recipe-detail-preparation-content.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailPreparationContentComponent {
  @Input() contentHtml!: string;
}

