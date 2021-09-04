import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from 'src/app/core/auth/auth.guard';
import { PageCreateRecipeComponent } from './page-create-recipe/page-create-recipe.component';
import { PageEditRecipeComponent } from './page-edit-recipe/page-edit-recipe.component';
import { PageRecipeComponent } from './page-recipe/page-recipe.component';

const routes: Routes = [
  {
    path: 'edit/:id',
    component: PageEditRecipeComponent,
    canActivate: [AuthGuard],
    data: { title: 'Edit recipe' },
  },
  { path: ':id/:slug', component: PageRecipeComponent },
  {
    path: 'create',
    component: PageCreateRecipeComponent,
    canActivate: [AuthGuard],
    data: { title: 'Create recipe' },
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class RecipesRoutingModule {}
