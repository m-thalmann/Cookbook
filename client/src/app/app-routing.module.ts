import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './core/auth/auth.guard';
import { LayoutComponent } from './layout/layout.component';
import { LogoutComponent } from './pages/logout/logout.component';
import { PageCreateRecipeComponent } from './pages/page-create-recipe/page-create-recipe.component';
import { PageEditRecipeComponent } from './pages/page-edit-recipe/page-edit-recipe.component';
import { PageHomeComponent } from './pages/page-home/page-home.component';
import { PageMyRecipesComponent } from './pages/page-my-recipes/page-my-recipes.component';
import { PageRecipeComponent } from './pages/page-recipe/page-recipe.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: '/home' },
  { path: 'logout', component: LogoutComponent },
  {
    path: '',
    component: LayoutComponent,
    children: [
      { path: 'home', component: PageHomeComponent },
      { path: 'my', component: PageMyRecipesComponent, canActivate: [AuthGuard] },
      { path: 'recipes/:id/:slug', component: PageRecipeComponent },
      { path: 'create', component: PageCreateRecipeComponent, canActivate: [AuthGuard] },
      { path: 'edit/:id', component: PageEditRecipeComponent, canActivate: [AuthGuard] },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
