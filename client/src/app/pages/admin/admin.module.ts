import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { MaterialModule } from 'src/app/core/material/material.module';
import { AdminRoutingModule } from './admin-routing.module';
import { PageAdminHomeComponent } from './page-admin-home/page-admin-home.component';
import { PageAdminUsersComponent } from './page-admin-users/page-admin-users.component';

@NgModule({
  declarations: [PageAdminUsersComponent, PageAdminHomeComponent],
  imports: [MaterialModule, CommonModule, AdminRoutingModule],
})
export class AdminModule {}
