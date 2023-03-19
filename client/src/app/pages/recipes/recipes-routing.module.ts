import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from 'src/app/core/auth/auth.guard';
import { RecipeDetailPageComponent } from './recipe-detail-page/recipe-detail-page.component';
import { RecipesPageComponent } from './recipes-page/recipes-page.component';
import { RecipesTrashPageComponent } from './recipes-trash-page/recipes-trash-page.component';

const routes: Routes = [
  { path: '', component: RecipesPageComponent },
  { path: 'trash', component: RecipesTrashPageComponent, canActivate: [AuthGuard], runGuardsAndResolvers: 'always' },
  { path: 'shared/:shareUuid', component: RecipeDetailPageComponent, data: { isOverlay: true } },
  { path: ':id', component: RecipeDetailPageComponent, data: { isOverlay: true } },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class RecipesRoutingModule {}
