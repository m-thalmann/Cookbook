import { HttpClientModule } from '@angular/common/http';
import { APP_INITIALIZER, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { ConfirmDialogComponent } from './components/confirm-dialog/confirm-dialog.component';
import { EditRecipeImagesComponent } from './components/create-edit-recipe/components/edit-recipe-images/edit-recipe-images.component';
import { EditRecipeInformationComponent } from './components/create-edit-recipe/components/edit-recipe-information/edit-recipe-information.component';
import { CreateEditRecipeComponent } from './components/create-edit-recipe/create-edit-recipe.component';
import { EditorComponent } from './components/editor/editor.component';
import { HcaptchaComponent } from './components/hcaptcha/hcaptcha.component';
import { PaginatorComponent } from './components/paginator/paginator.component';
import { RecipeItemComponent } from './components/recipe-item/recipe-item.component';
import { RecipeListComponent } from './components/recipe-list/recipe-list.component';
import { ConfigService } from './core/config/config.service';
import { ImageFallbackDirective } from './core/directives/image-fallback.directive';
import { InputFocusDirective } from './core/directives/input-focus.directive';
import { MaterialModule } from './core/material/material.module';
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
import { PageRecipeComponent } from './pages/page-recipe/page-recipe.component';
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
    RecipeItemComponent,
    ImageFallbackDirective,
    PaginatorComponent,
    SettingsDialogComponent,
    PageMyRecipesComponent,
    RecipeListComponent,
    PageRecipeComponent,
    CreateEditRecipeComponent,
    PageCreateRecipeComponent,
    PageEditRecipeComponent,
    EditRecipeInformationComponent,
    EditRecipeImagesComponent,
    EditorComponent,
    InputFocusDirective,
    ConfirmDialogComponent,
    PageSearchComponent,
    PageCategoriesComponent,
    PageNotFoundComponent,
    HcaptchaComponent,
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
