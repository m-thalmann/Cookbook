import { Component, Input } from '@angular/core';
import { Recipe } from 'src/app/core/api/ApiInterfaces';
import { calculateTotalTime } from 'src/app/core/functions';
import { TranslationService } from 'src/app/core/i18n/translation.service';

@Component({
  selector: 'cb-recipe-tags[recipe]',
  templateUrl: './recipe-tags.component.html',
  styleUrls: ['./recipe-tags.component.scss'],
})
export class RecipeTagsComponent {
  @Input() recipe!: Recipe;
  @Input() compact = false;

  constructor(public translation: TranslationService) {}

  get categoryURL() {
    if (!this.recipe?.category) {
      return null;
    }

    return '/categories/' + encodeURIComponent(this.recipe?.category);
  }

  get totalTime() {
    return calculateTotalTime(this.recipe);
  }

  get showLanguage() {
    return !this.compact || this.translation.language !== this.recipe.languageCode;
  }
}
