import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { CookbookDetailPageComponent } from './cookbook-detail-page/cookbook-detail-page.component';
import { CookbookRecipesPageComponent } from './cookbook-recipes-page/cookbook-recipes-page.component';
import { CookbooksPageComponent } from './cookbooks-page/cookbooks-page.component';

const routes: Routes = [
  { path: '', component: CookbooksPageComponent, data: { showAddButton: true } },
  {
    path: ':id',
    children: [
      { path: '', component: CookbookDetailPageComponent },
      { path: 'recipes', component: CookbookRecipesPageComponent },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class CookbooksRoutingModule {}
