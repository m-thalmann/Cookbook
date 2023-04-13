import { Routes } from '@angular/router';
import { AuthGuard } from './core/auth/auth.guard';
import { GuestGuard } from './core/auth/guest.guard';
import { LayoutAuthComponent } from './layouts/layout-auth/layout-auth.component';
import { LayoutDefaultComponent } from './layouts/layout-default/layout-default.component';

export default [
  { path: '', pathMatch: 'full', redirectTo: '/home' },

  {
    path: 'verify-email/:userId/:token',
    loadComponent: () =>
      import('./pages/auth/verify-email-page/verify-email-page.component').then(
        (comp) => comp.VerifyEmailPageComponent
      ),
  },

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
      {
        path: 'password-reset/:email/:token',
        loadComponent: () =>
          import('./pages/auth/password-reset-page/password-reset-page.component').then(
            (comp) => comp.PasswordResetPageComponent
          ),
      },
    ],
  },

  {
    path: '',
    component: LayoutDefaultComponent,
    children: [
      {
        path: 'home',
        loadComponent: () => import('./pages/home-page/home-page.component').then((comp) => comp.HomePageComponent),
        data: { showAddButton: true },
      },
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
