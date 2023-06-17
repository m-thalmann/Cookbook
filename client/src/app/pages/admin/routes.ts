import { Routes } from '@angular/router';
import { AdminPageLayoutComponent } from './admin-page-layout/admin-page-layout.component';
import { AdminRecipesPageComponent } from './admin-recipes-page/admin-recipes-page.component';
import { AdminUsersPageComponent } from './admin-users-page/admin-users-page.component';

export default [
  {
    path: '',
    component: AdminPageLayoutComponent,
    children: [
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
