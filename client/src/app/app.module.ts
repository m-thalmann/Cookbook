import { HttpClientModule } from '@angular/common/http';
import { APP_INITIALIZER, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { ComponentsModule } from './components/components.module';
import { ConfigService } from './core/config/config.service';
import { CoreModule } from './core/core.module';
import { ResetPasswordDialogComponent } from './layout/components/login-register-dialog/components/reset-password-dialog/reset-password-dialog.component';
import { VerifyEmailDialogComponent } from './layout/components/login-register-dialog/components/verify-email-dialog/verify-email-dialog.component';
import { LoginRegisterDialogComponent } from './layout/components/login-register-dialog/login-register-dialog.component';
import { SettingsDialogComponent } from './layout/components/settings-dialog/settings-dialog.component';
import { LayoutComponent } from './layout/layout.component';
import { LogoutComponent } from './pages/logout/logout.component';
import { PageCategoriesComponent } from './pages/page-categories/page-categories.component';
import { PageCreateRecipeComponent } from './pages/page-create-recipe/page-create-recipe.component';
import { PageEditRecipeComponent } from './pages/page-edit-recipe/page-edit-recipe.component';
import { PageHomeComponent } from './pages/page-home/page-home.component';
import { PageMyRecipesComponent } from './pages/page-my-recipes/page-my-recipes.component';
import { PageNotFoundComponent } from './pages/page-not-found/page-not-found.component';
import { RecipeImageSliderComponent } from './pages/page-recipe/components/recipe-image-slider/recipe-image-slider.component';
import { PageRecipeComponent } from './pages/page-recipe/page-recipe.component';
import { SkeletonPageRecipeComponent } from './pages/page-recipe/skeleton/skeleton-page-recipe.component';
import { PageSearchComponent } from './pages/page-search/page-search.component';

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
    SettingsDialogComponent,
    PageMyRecipesComponent,
    PageRecipeComponent,
    PageCreateRecipeComponent,
    PageEditRecipeComponent,
    PageSearchComponent,
    PageCategoriesComponent,
    PageNotFoundComponent,
    RecipeImageSliderComponent,
    VerifyEmailDialogComponent,
    ResetPasswordDialogComponent,
    SkeletonPageRecipeComponent,
  ],
  imports: [BrowserModule, AppRoutingModule, BrowserAnimationsModule, HttpClientModule, ComponentsModule, CoreModule],
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
