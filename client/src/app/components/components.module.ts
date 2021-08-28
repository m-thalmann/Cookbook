import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { RouterModule } from '@angular/router';
import { CoreModule } from '../core/core.module';
import { ConfirmDialogComponent } from './confirm-dialog/confirm-dialog.component';
import { EditRecipeImagesComponent } from './create-edit-recipe/components/edit-recipe-images/edit-recipe-images.component';
import { EditRecipeInformationComponent } from './create-edit-recipe/components/edit-recipe-information/edit-recipe-information.component';
import { CreateEditRecipeComponent } from './create-edit-recipe/create-edit-recipe.component';
import { EditorComponent } from './editor/editor.component';
import { HcaptchaComponent } from './hcaptcha/hcaptcha.component';
import { ImageComponent } from './image/image.component';
import { InputDialogComponent } from './input-dialog/input-dialog.component';
import { PaginatorComponent } from './paginator/paginator.component';
import { RecipeItemComponent } from './recipe-item/recipe-item.component';
import { SkeletonRecipeItemComponent } from './recipe-item/skeleton/skeleton-recipe-item.component';
import { RecipeListComponent } from './recipe-list/recipe-list.component';
import { SkeletonComponent } from './skeleton/skeleton.component';

const components = [
  ConfirmDialogComponent,
  EditRecipeImagesComponent,
  EditRecipeInformationComponent,
  CreateEditRecipeComponent,
  EditorComponent,
  HcaptchaComponent,
  ImageComponent,
  PaginatorComponent,
  RecipeItemComponent,
  SkeletonRecipeItemComponent,
  RecipeListComponent,
  SkeletonComponent,
  InputDialogComponent,
];

@NgModule({
  declarations: components,
  exports: components,
  imports: [CommonModule, CoreModule, RouterModule],
})
export class ComponentsModule {}
