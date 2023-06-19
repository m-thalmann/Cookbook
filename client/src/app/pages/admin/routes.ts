import { Routes } from '@angular/router';
import { AdminDashboardPageComponent } from './admin-dashboard-page/admin-dashboard-page.component';
import { AdminPageLayoutComponent } from './admin-page-layout/admin-page-layout.component';
import { AdminRecipesPageComponent } from './admin-recipes-page/admin-recipes-page.component';
import { AdminUsersPageComponent } from './admin-users-page/admin-users-page.component';

export default [
  { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
  {
    path: '',
    component: AdminPageLayoutComponent,
    children: [
      {
        path: 'dashboard',
        component: AdminDashboardPageComponent,
        data: { title: 'pages.admin.area' },
      },
      {
        path: 'users',
        component: AdminUsersPageComponent,
        data: { title: 'pages.admin.area' },
      },
      {
        path: 'recipes',
        component: AdminRecipesPageComponent,
        data: { title: 'pages.admin.area' },
      },
    ],
  },
] as Routes;
