import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule } from '@angular/router';
import { CoreModule } from '../core/core.module';
import { CategoryChipListComponent } from './category-chip-list/category-chip-list.component';
import { CookbookCardComponent } from './cookbook-card/cookbook-card.component';
import { ConfirmDialogComponent } from './dialogs/confirm-dialog/confirm-dialog.component';
import { PromptDialogComponent } from './dialogs/prompt-dialog/prompt-dialog.component';
import { HcaptchaComponent } from './hcaptcha/hcaptcha.component';
import { ImageSliderDialogComponent } from './image-slider-dialog/image-slider-dialog.component';
import { NumberInputComponent } from './number-input/number-input.component';
import { PageSectionComponent } from './page-section/page-section.component';
import { RecipeCardComponent } from './recipe-card/recipe-card.component';
import { RecipeSearchComponent } from './recipe-search/recipe-search.component';
import { SearchBarComponent } from './search-bar/search-bar.component';
import { ShareMenuComponent } from './share-menu/share-menu.component';
import { IconSnackbarComponent } from './snackbar/icon-snackbar/icon-snackbar.component';

const components = [
  CookbookCardComponent,
  ImageSliderDialogComponent,
  NumberInputComponent,
  RecipeCardComponent,
  SearchBarComponent,
  ShareMenuComponent,
  IconSnackbarComponent,
  PageSectionComponent,
  CategoryChipListComponent,
  RecipeSearchComponent,
  ConfirmDialogComponent,
  PromptDialogComponent,
  HcaptchaComponent,
];

@NgModule({
  declarations: components,
  exports: components,
  imports: [CommonModule, RouterModule, CoreModule],
})
export class ComponentsModule {}
