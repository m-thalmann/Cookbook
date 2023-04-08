import { Routes } from '@angular/router';
import { AuthGuard } from './core/auth/auth.guard';
import { GuestGuard } from './core/auth/guest.guard';
import { LayoutAuthComponent } from './layouts/layout-auth/layout-auth.component';
import { LayoutDefaultComponent } from './layouts/layout-default/layout-default.component';
import { HomePageComponent } from './pages/home-page/home-page.component';

export default [
  { path: '', pathMatch: 'full', redirectTo: '/home' },

  {
    path: '',
    component: LayoutAuthComponent,
    canActivate: [GuestGuard],
    children: [
      {
        path: 'login',
        loadComponent: () =>
          import('./pages/auth/login-page/login-page.component').then((comp) => comp.LoginPageComponent),
      },
      {
        path: 'sign-up',
        loadComponent: () =>
          import('./pages/auth/sign-up-page/sign-up-page.component').then((comp) => comp.SignUpPageComponent),
      },
    ],
  },

  {
    path: '',
    component: LayoutDefaultComponent,
    children: [
      { path: 'home', component: HomePageComponent, data: { showAddButton: true } },
      { path: 'recipes', loadChildren: () => import('./pages/recipes/routes') },
      {
        path: 'cookbooks',
        canActivate: [AuthGuard],
        runGuardsAndResolvers: 'always',
        loadChildren: () => import('./pages/cookbooks/routes'),
      },
      {
        path: 'settings',
        loadChildren: () => import('./pages/settings/routes'),
      },
    ],
  },
] as Routes;
