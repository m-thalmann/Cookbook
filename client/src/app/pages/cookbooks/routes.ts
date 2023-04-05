import { Routes } from '@angular/router';
import { CookbookDetailPageComponent } from './cookbook-detail-page/cookbook-detail-page.component';
import { CookbookRecipesPageComponent } from './cookbook-recipes-page/cookbook-recipes-page.component';
import { CookbooksPageComponent } from './cookbooks-page/cookbooks-page.component';

export default [
  { path: '', component: CookbooksPageComponent, data: { showAddButton: true } },
  {
    path: ':id',
    children: [
      { path: '', component: CookbookDetailPageComponent },
      { path: 'recipes', component: CookbookRecipesPageComponent },
    ],
  },
] as Routes;
