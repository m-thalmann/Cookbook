import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { CoreModule } from 'src/app/core/core.module';
import { ComponentsModule } from 'src/app/components/components.module';
import { CookbooksRoutingModule } from './cookbooks-routing.module';
import { CookbooksPageComponent } from './cookbooks-page/cookbooks-page.component';
import { CookbookDetailPageComponent } from './cookbook-detail-page/cookbook-detail-page.component';
import { CookbookRecipesPageComponent } from './cookbook-recipes-page/cookbook-recipes-page.component';
import { CookbookHeaderComponent } from './components/cookbook-header/cookbook-header.component';

@NgModule({
  declarations: [
    CookbooksPageComponent,
    CookbookDetailPageComponent,
    CookbookRecipesPageComponent,
    CookbookHeaderComponent,
  ],
  imports: [CommonModule, CookbooksRoutingModule, CoreModule, ComponentsModule],
})
export class CookbooksModule {}
