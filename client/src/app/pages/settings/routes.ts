import { Routes } from '@angular/router';
import { AuthGuard } from 'src/app/core/auth/auth.guard';
import { AccountSettingsPageComponent } from './account-settings-page/account-settings-page.component';
import { GeneralSettingsPageComponent } from './general-settings-page/general-settings-page.component';
import { SecuritySettingsPageComponent } from './security-settings-page/security-settings-page.component';
import { SettingsPageLayoutComponent } from './settings-page-layout/settings-page-layout.component';

export default [
  { path: '', pathMatch: 'full', redirectTo: 'general' },
  {
    path: '',
    component: SettingsPageLayoutComponent,
    children: [
      {
        path: 'general',
        component: GeneralSettingsPageComponent,
        data: { title: 'pages.settings.title' },
      },
      {
        path: 'account',
        canActivate: [AuthGuard],
        runGuardsAndResolvers: 'always',
        component: AccountSettingsPageComponent,
        data: { title: 'pages.settings.title' },
      },
      {
        path: 'security',
        canActivate: [AuthGuard],
        runGuardsAndResolvers: 'always',
        component: SecuritySettingsPageComponent,
        data: { title: 'pages.settings.title' },
      },
    ],
  },
] as Routes;
