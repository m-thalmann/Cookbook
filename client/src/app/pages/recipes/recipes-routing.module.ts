import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { RecipeDetailPageComponent } from './recipe-detail-page/recipe-detail-page.component';
import { RecipesPageComponent } from './recipes-page/recipes-page.component';

const routes: Routes = [
  { path: '', component: RecipesPageComponent },
  { path: 'shared/:shareUuid', component: RecipeDetailPageComponent, data: { isOverlay: true } },
  { path: ':id', component: RecipeDetailPageComponent, data: { isOverlay: true } },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class RecipesRoutingModule {}

