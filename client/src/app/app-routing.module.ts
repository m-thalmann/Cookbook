import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { AuthGuard } from './core/auth/auth.guard';
import { PreloadService } from './core/services/preload.service';
import { LayoutComponent } from './layout/layout.component';
import { LogoutComponent } from './pages/logout/logout.component';
import { PageCategoriesComponent } from './pages/page-categories/page-categories.component';
import { PageHomeComponent } from './pages/page-home/page-home.component';
import { PageMyRecipesComponent } from './pages/page-my-recipes/page-my-recipes.component';
import { PageNotFoundComponent } from './pages/page-not-found/page-not-found.component';
import { PageSearchComponent } from './pages/page-search/page-search.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: '/home' },
  { path: 'logout', component: LogoutComponent },
  {
    path: '',
    component: LayoutComponent,
    children: [
      { path: 'home', component: PageHomeComponent, data: { title: 'Home' } },
      { path: 'my', component: PageMyRecipesComponent, canActivate: [AuthGuard], data: { title: 'My recipes' } },
      {
        path: 'search',
        children: [
          { path: '', component: PageSearchComponent },
          { path: ':search', component: PageSearchComponent },
        ],
        data: { title: 'Search' },
      },
      {
        path: 'categories',
        children: [
          { path: '', component: PageCategoriesComponent, data: { title: 'Categories' } },
          { path: ':category', component: PageCategoriesComponent, data: { titleFromParam: 'category' } },
        ],
      },
      {
        path: 'recipes',
        loadChildren: () => import('./pages/recipes/recipes.module').then((m) => m.RecipesModule),
        data: { preload: true, delay: true },
      },
      {
        path: 'admin',
        loadChildren: () => import('./pages/admin/admin.module').then((m) => m.AdminModule),
        canActivate: [AuthGuard],
        data: { admin: true },
      },
      { path: 'not-found', component: PageNotFoundComponent, data: { title: 'Page not found' } },
    ],
  },
  { path: '**', redirectTo: '/not-found' },
];

@NgModule({
  imports: [
    RouterModule.forRoot(routes, {
      preloadingStrategy: PreloadService,
    }),
  ],
  exports: [RouterModule],
})
export class AppRoutingModule {}
