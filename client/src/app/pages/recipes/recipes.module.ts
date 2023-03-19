import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RecipesRoutingModule } from './recipes-routing.module';
import { RecipeDetailPageComponent } from './recipe-detail-page/recipe-detail-page.component';
import { RecipeDetailComponent } from './recipe-detail-page/components/recipe-detail/recipe-detail.component';
import { RecipeDetailImageSliderComponent } from './recipe-detail-page/components/recipe-detail-image-slider/recipe-detail-image-slider.component';
import { RecipePublicShareDialogComponent } from './recipe-detail-page/components/recipe-public-share-dialog/recipe-public-share-dialog.component';
import { RecipesPageComponent } from './recipes-page/recipes-page.component';
import { RecipeDetailSectionComponent } from './recipe-detail-page/components/recipe-detail-section/recipe-detail-section.component';
import { RecipeDetailPreparationContentComponent } from './recipe-detail-page/components/recipe-detail-preparation-content/recipe-detail-preparation-content.component';
import { RecipeDetailHeaderComponent } from './recipe-detail-page/components/recipe-detail-header/recipe-detail-header.component';
import { CoreModule } from 'src/app/core/core.module';
import { ComponentsModule } from 'src/app/components/components.module';
import { RecipesTrashPageComponent } from './recipes-trash-page/recipes-trash-page.component';

@NgModule({
  declarations: [
    RecipeDetailPageComponent,
    RecipeDetailComponent,
    RecipeDetailImageSliderComponent,
    RecipePublicShareDialogComponent,
    RecipesPageComponent,
    RecipeDetailSectionComponent,
    RecipeDetailPreparationContentComponent,
    RecipeDetailHeaderComponent,
    RecipesTrashPageComponent,
  ],
  imports: [CommonModule, RecipesRoutingModule, CoreModule, ComponentsModule],
})
export class RecipesModule {}

