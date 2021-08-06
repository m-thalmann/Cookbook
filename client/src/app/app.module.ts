import { HttpClientModule } from '@angular/common/http';
import { APP_INITIALIZER, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { ConfigService } from './core/config/config.service';
import { MaterialModule } from './core/material/material.module';
import { LayoutComponent } from './layout/layout.component';
import { PageHomeComponent } from './pages/page-home/page-home.component';
import { LoginRegisterDialogComponent } from './layout/components/login-register-dialog/login-register-dialog.component';
import { LogoutComponent } from './pages/logout/logout.component';
import { RecipeItemComponent } from './components/recipe-item/recipe-item.component';
import { ImageFallbackDirective } from './core/directives/image-fallback.directive';
import { PaginatorComponent } from './components/paginator/paginator.component';
import { SettingsDialogComponent } from './layout/components/settings-dialog/settings-dialog.component';
import { PageMyRecipesComponent } from './pages/page-my-recipes/page-my-recipes.component';
import { RecipeListComponent } from './components/recipe-list/recipe-list.component';
import { PageRecipeComponent } from './pages/page-recipe/page-recipe.component';

export function setupConfig(service: ConfigService) {
  return () => service.load();
}

@NgModule({
  declarations: [
    AppComponent,
    LayoutComponent,
    PageHomeComponent,
    LoginRegisterDialogComponent,
    LogoutComponent,
    RecipeItemComponent,
    ImageFallbackDirective,
    PaginatorComponent,
    SettingsDialogComponent,
    PageMyRecipesComponent,
    RecipeListComponent,
    PageRecipeComponent,
  ],
  imports: [BrowserModule, AppRoutingModule, BrowserAnimationsModule, MaterialModule, HttpClientModule],
  providers: [
    {
      provide: APP_INITIALIZER,
      useFactory: setupConfig,
      deps: [ConfigService],
      multi: true,
    },
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}
