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
    data: { title: 'auth.verifyingEmail' },
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
        data: { title: 'auth.login' },
      },
      {
        path: 'sign-up',
        loadComponent: () =>
          import('./pages/auth/sign-up-page/sign-up-page.component').then((comp) => comp.SignUpPageComponent),
        data: { title: 'auth.signUp' },
      },
      {
        path: 'password-reset/:email/:token',
        loadComponent: () =>
          import('./pages/auth/password-reset-page/password-reset-page.component').then(
            (comp) => comp.PasswordResetPageComponent
          ),
        data: { title: 'auth.resetPassword' },
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
        data: { preload: true, showAddButton: true, title: 'pages.home.title' },
      },
      { path: 'recipes', loadChildren: () => import('./pages/recipes/routes'), data: { preload: true } },
      {
        path: 'cookbooks',
        canActivate: [AuthGuard],
        runGuardsAndResolvers: 'always',
        loadChildren: () => import('./pages/cookbooks/routes'),
        data: { preload: true },
      },
      {
        path: 'settings',
        loadChildren: () => import('./pages/settings/routes'),
      },

      {
        path: 'not-found',
        loadComponent: () =>
          import('./pages/not-found-page/not-found-page.component').then((comp) => comp.NotFoundPageComponent),
        data: { preload: true, title: 'pages.notFound.title' },
      },
    ],
  },

  {
    path: '**',
    redirectTo: '/not-found',
  },
] as Routes;
