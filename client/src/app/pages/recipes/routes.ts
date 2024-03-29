import { Routes } from '@angular/router';
import { AuthGuard } from 'src/app/core/auth/auth.guard';
import { CreateRecipePageComponent } from './create-recipe-page/create-recipe-page.component';
import { EditRecipePageComponent } from './edit-recipe-page/edit-recipe-page.component';
import { RecipeDetailPageComponent } from './recipe-detail-page/recipe-detail-page.component';
import { RecipesPageComponent } from './recipes-page/recipes-page.component';
import { RecipesTrashPageComponent } from './recipes-trash-page/recipes-trash-page.component';

export default [
  { path: '', component: RecipesPageComponent, data: { showAddButton: true, title: 'recipes.recipes' } },
  {
    path: 'trash',
    component: RecipesTrashPageComponent,
    canActivate: [AuthGuard],
    runGuardsAndResolvers: 'always',
    data: { title: 'pages.trash.title' },
  },
  {
    path: 'create',
    component: CreateRecipePageComponent,
    canActivate: [AuthGuard],
    runGuardsAndResolvers: 'always',
    data: { title: 'pages.recipes.children.create.title' },
  },
  { path: 'shared/:shareUuid', component: RecipeDetailPageComponent, data: { isOverlay: true } },
  { path: ':id', component: RecipeDetailPageComponent, data: { isOverlay: true } },
  {
    path: ':id/edit',
    component: EditRecipePageComponent,
    canActivate: [AuthGuard],
    runGuardsAndResolvers: 'always',
    data: { title: 'pages.recipes.children.edit.title' },
  },
] as Routes;
