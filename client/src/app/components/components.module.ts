import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule } from '@angular/router';
import { CoreModule } from '../core/core.module';
import { CookbookCardComponent } from './cookbook-card/cookbook-card.component';
import { ImageSliderDialogComponent } from './image-slider-dialog/image-slider-dialog.component';
import { NumberInputComponent } from './number-input/number-input.component';
import { RecipeCardComponent } from './recipe-card/recipe-card.component';
import { SearchBarComponent } from './search-bar/search-bar.component';
import { ShareMenuComponent } from './share-menu/share-menu.component';

const components = [
  CookbookCardComponent,
  ImageSliderDialogComponent,
  NumberInputComponent,
  RecipeCardComponent,
  SearchBarComponent,
  ShareMenuComponent,
];

@NgModule({
  declarations: components,
  exports: components,
  imports: [CommonModule, RouterModule, CoreModule],
})
export class ComponentsModule {}
