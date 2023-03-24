import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from 'src/app/core/auth/auth.guard';
import { GeneralSettingsPageComponent } from './general-settings-page/general-settings-page.component';
import { SettingsPageLayoutComponent } from './settings-page-layout/settings-page-layout.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'general' },
  {
    path: '',
    component: SettingsPageLayoutComponent,
    children: [
      { path: 'general', component: GeneralSettingsPageComponent },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class SettingsRoutingModule {}
