import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { ComponentsModule } from 'src/app/components/components.module';
import { CoreModule } from 'src/app/core/core.module';
import { AdminRoutingModule } from './admin-routing.module';
import { PageAdminHomeComponent } from './page-admin-home/page-admin-home.component';
import { PageAdminRecipesComponent } from './page-admin-recipes/page-admin-recipes.component';
import { CreateUserDialogComponent } from './page-admin-users/components/create-user-dialog/create-user-dialog.component';
import { PageAdminUsersComponent } from './page-admin-users/page-admin-users.component';

@NgModule({
  declarations: [PageAdminUsersComponent, PageAdminHomeComponent, CreateUserDialogComponent, PageAdminRecipesComponent],
  imports: [CoreModule, CommonModule, AdminRoutingModule, ComponentsModule],
})
export class AdminModule {}
