import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { MaterialModule } from 'src/app/core/material/material.module';
import { AdminRoutingModule } from './admin-routing.module';
import { PageAdminHomeComponent } from './page-admin-home/page-admin-home.component';
import { PageAdminUsersComponent } from './page-admin-users/page-admin-users.component';
import { CreateUserDialogComponent } from './page-admin-users/components/create-user-dialog/create-user-dialog.component';

@NgModule({
  declarations: [PageAdminUsersComponent, PageAdminHomeComponent, CreateUserDialogComponent],
  imports: [MaterialModule, CommonModule, AdminRoutingModule],
})
export class AdminModule {}
