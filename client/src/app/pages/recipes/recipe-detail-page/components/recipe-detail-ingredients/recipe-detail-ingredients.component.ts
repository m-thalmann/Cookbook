import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AmountPipe } from 'src/app/core/pipes/amount.pipe';
import { DetailedRecipe } from 'src/app/core/models/recipe';

@Component({
  selector: 'app-recipe-detail-ingredients',
  templateUrl: './recipe-detail-ingredients.component.html',
  styleUrls: ['./recipe-detail-ingredients.component.scss'],
  standalone: true,
  imports: [CommonModule, AmountPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailIngredientsComponent {
  @Input() ingredients!: DetailedRecipe['ingredients'];
  @Input() portionsMultiplier: number = 1;

  getIngredientAmount(amount: number | null) {
    if (amount === null) {
      return null;
    }

    amount *= this.portionsMultiplier;

    return Math.round(amount * 100) / 100;
  }
}

