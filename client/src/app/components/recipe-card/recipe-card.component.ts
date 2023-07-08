import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { ListRecipe } from 'src/app/core/models/recipe';
import { PLACEHOLDER_RECIPE_IMAGE_URL } from 'src/app/core/models/recipe-image';
import { SkeletonComponent } from '../skeleton/skeleton.component';

@Component({
  selector: 'app-recipe-card',
  templateUrl: './recipe-card.component.html',
  styleUrls: ['./recipe-card.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterLink, TranslocoModule, MatIconModule, SkeletonComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeCardComponent {
  @Input() recipe!: ListRecipe | null;

  constructor() {}

  get thumbnailImageCSS() {
    const url = this.recipe?.thumbnail?.url || PLACEHOLDER_RECIPE_IMAGE_URL;

    return `background-image: url(${url})`;
  }
}
