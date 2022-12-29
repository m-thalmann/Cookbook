import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './core/auth/auth.guard';
import { GuestGuard } from './core/auth/guest.guard';
import { LayoutAuthComponent } from './layouts/layout-auth/layout-auth.component';
import { LayoutDefaultComponent } from './layouts/layout-default/layout-default.component';
import { LoginPageComponent } from './pages/auth/login-page/login-page.component';
import { SignUpPageComponent } from './pages/auth/sign-up-page/sign-up-page.component';
import { CookbooksPageComponent } from './pages/cookbooks/cookbooks-page/cookbooks-page.component';
import { HomePageComponent } from './pages/home-page/home-page.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: '/home' },

  {
    path: '',
    component: LayoutAuthComponent,
    canActivate: [GuestGuard],
    children: [
      { path: 'login', component: LoginPageComponent },
      { path: 'sign-up', component: SignUpPageComponent },
    ],
  },

  {
    path: '',
    component: LayoutDefaultComponent,
    children: [
      { path: 'home', component: HomePageComponent },
      { path: 'recipes', loadChildren: () => import('./pages/recipes/recipes.module').then((m) => m.RecipesModule) },
      {
        path: 'cookbooks',
        component: CookbooksPageComponent,
        canActivate: [AuthGuard],
        runGuardsAndResolvers: 'always',
      },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
