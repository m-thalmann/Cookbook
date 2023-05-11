import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { Ingredient } from 'src/app/core/models/ingredient';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { AmountPipe } from 'src/app/core/pipes/amount.pipe';

@Component({
  selector: 'app-recipe-detail-ingredients',
  templateUrl: './recipe-detail-ingredients.component.html',
  styleUrls: ['./recipe-detail-ingredients.component.scss'],
  standalone: true,
  imports: [CommonModule, SkeletonComponent, AmountPipe, RepeatDirective],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailIngredientsComponent {
  @Input() ingredients!: DetailedRecipe['ingredients'] | null;
  @Input() portionsMultiplier: number = 1;

  getIngredientAmount(amount: number | null) {
    if (amount === null) {
      return null;
    }

    amount *= this.portionsMultiplier;

    return Math.round(amount * 100) / 100;
  }

  trackByGroup(index: number, group: DetailedRecipe['ingredients'][0]) {
    return group.group;
  }

  trackByIngredient(index: number, ingredient: Ingredient) {
    return ingredient.id;
  }
}
