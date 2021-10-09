import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { RecipesRoutingModule } from './recipes-routing.module';
import { CoreModule } from 'src/app/core/core.module';
import { ComponentsModule } from 'src/app/components/components.module';
import { PageCreateRecipeComponent } from './page-create-recipe/page-create-recipe.component';
import { PageEditRecipeComponent } from './page-edit-recipe/page-edit-recipe.component';
import { PageRecipeComponent } from './page-recipe/page-recipe.component';
import { RecipeImageSliderComponent } from './page-recipe/components/recipe-image-slider/recipe-image-slider.component';
import { SkeletonPageRecipeComponent } from './page-recipe/skeleton/skeleton-page-recipe.component';
import { CreateEditRecipeComponent } from './components/create-edit-recipe/create-edit-recipe.component';
import { EditRecipeImagesComponent } from './components/create-edit-recipe/components/edit-recipe-images/edit-recipe-images.component';
import { EditRecipeInformationComponent } from './components/create-edit-recipe/components/edit-recipe-information/edit-recipe-information.component';
import { EditorComponent } from './components/editor/editor.component';

@NgModule({
  declarations: [
    PageRecipeComponent,
    PageCreateRecipeComponent,
    PageEditRecipeComponent,
    RecipeImageSliderComponent,
    SkeletonPageRecipeComponent,
    CreateEditRecipeComponent,
    EditRecipeImagesComponent,
    EditRecipeInformationComponent,
    EditorComponent,
  ],
  imports: [CommonModule, RecipesRoutingModule, CoreModule, ComponentsModule],
})
export class RecipesModule {}
