import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { GuestGuard } from './core/auth/guest.guard';
import { LayoutAuthComponent } from './layouts/layout-auth/layout-auth.component';
import { LayoutDefaultComponent } from './layouts/layout-default/layout-default.component';
import { LoginPageComponent } from './pages/auth/login-page/login-page.component';
import { SignUpPageComponent } from './pages/auth/sign-up-page/sign-up-page.component';
import { HomePageComponent } from './pages/home-page/home-page.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: '/dashboard' },

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
    children: [{ path: 'dashboard', component: HomePageComponent }],
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
