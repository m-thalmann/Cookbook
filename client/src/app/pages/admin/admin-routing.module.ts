import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { PageAdminHomeComponent } from './page-admin-home/page-admin-home.component';
import { PageAdminRecipesComponent } from './page-admin-recipes/page-admin-recipes.component';
import { PageAdminUsersComponent } from './page-admin-users/page-admin-users.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', component: PageAdminHomeComponent, data: { title: 'Admin Area' } },
  { path: 'users', component: PageAdminUsersComponent, data: { title: 'Admin Users' } },
  { path: 'recipes', component: PageAdminRecipesComponent, data: { title: 'Admin Recipes' } },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AdminRoutingModule {}
