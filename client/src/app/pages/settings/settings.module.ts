import { CommonModule } from '@angular/common';
import { NgModule } from '@angular/core';

import { ComponentsModule } from 'src/app/components/components.module';
import { CoreModule } from 'src/app/core/core.module';
import { GeneralSettingsPageComponent } from './general-settings-page/general-settings-page.component';
import { SettingsRoutingModule } from './settings-routing.module';
import { SettingsPageLayoutComponent } from './settings-page-layout/settings-page-layout.component';

@NgModule({
  declarations: [
    GeneralSettingsPageComponent,
    SettingsPageLayoutComponent,
  ],
  imports: [CommonModule, SettingsRoutingModule, CoreModule, ComponentsModule],
})
export class SettingsModule {}
