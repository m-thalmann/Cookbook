import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatSortModule } from '@angular/material/sort';
import { MatTableModule } from '@angular/material/table';
import { ComponentsModule } from 'src/app/components/components.module';
import { CoreModule } from 'src/app/core/core.module';
import { AdminRoutingModule } from './admin-routing.module';
import { PageAdminHomeComponent } from './page-admin-home/page-admin-home.component';
import { PageAdminRecipesComponent } from './page-admin-recipes/page-admin-recipes.component';
import { PageAdminServerComponent } from './page-admin-server/page-admin-server.component';
import { CreateUserDialogComponent } from './page-admin-users/components/create-user-dialog/create-user-dialog.component';
import { PageAdminUsersComponent } from './page-admin-users/page-admin-users.component';

const MATERIAL_IMPORTS = [MatTableModule, MatSortModule, MatCheckboxModule];

@NgModule({
  declarations: [
    PageAdminUsersComponent,
    PageAdminHomeComponent,
    CreateUserDialogComponent,
    PageAdminRecipesComponent,
    PageAdminServerComponent,
  ],
  imports: [CoreModule, CommonModule, AdminRoutingModule, ComponentsModule, ...MATERIAL_IMPORTS],
})
export class AdminModule {}
