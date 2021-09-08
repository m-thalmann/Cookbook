import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';
import { PageAdminHomeComponent } from './page-admin-home/page-admin-home.component';
import { PageAdminRecipesComponent } from './page-admin-recipes/page-admin-recipes.component';
import { PageAdminServerComponent } from './page-admin-server/page-admin-server.component';
import { PageAdminUsersComponent } from './page-admin-users/page-admin-users.component';

const routes: Routes = [
  { path: '', pathMatch: 'full', component: PageAdminHomeComponent, data: { title: 'pages.admin.area.title' } },
  { path: 'users', component: PageAdminUsersComponent, data: { title: 'pages.admin.users.title' } },
  { path: 'recipes', component: PageAdminRecipesComponent, data: { title: 'pages.admin.recipes.title' } },
  { path: 'server', component: PageAdminServerComponent, data: { title: 'pages.admin.server.title' } },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class AdminRoutingModule {}
