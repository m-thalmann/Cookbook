import { Routes } from '@angular/router';
import { AuthGuard } from 'src/app/core/auth/auth.guard';
import { CookbookDetailPageComponent } from './cookbook-detail-page/cookbook-detail-page.component';
import { CookbookRecipesPageComponent } from './cookbook-recipes-page/cookbook-recipes-page.component';
import { CookbooksPageComponent } from './cookbooks-page/cookbooks-page.component';
import { EditCookbookPageComponent } from './edit-cookbook-page/edit-cookbook-page.component';

export default [
  { path: '', component: CookbooksPageComponent, data: { showAddButton: true, title: 'cookbooks.cookbooks' } },
  {
    path: ':id',
    children: [
      { path: '', component: CookbookDetailPageComponent },
      {
        path: 'edit',
        component: EditCookbookPageComponent,
        canActivate: [AuthGuard],
        runGuardsAndResolvers: 'always',
        data: { title: 'pages.cookbooks.children.edit.title' },
      },
      { path: 'recipes', component: CookbookRecipesPageComponent, data: { title: 'recipes.recipes' } },
    ],
  },
] as Routes;
