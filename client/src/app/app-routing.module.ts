import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './core/auth/auth.guard';
import { LayoutComponent } from './layout/layout.component';
import { LogoutComponent } from './pages/logout/logout.component';
import { PageHomeComponent } from './pages/page-home/page-home.component';
import { PageMyRecipesComponent } from './pages/page-my-recipes/page-my-recipes.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: '/home' },
  { path: 'logout', component: LogoutComponent },
  {
    path: '',
    component: LayoutComponent,
    canActivate: [AuthGuard],
    children: [
      { path: 'home', component: PageHomeComponent },
      { path: 'my', component: PageMyRecipesComponent },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule],
})
export class AppRoutingModule {}
