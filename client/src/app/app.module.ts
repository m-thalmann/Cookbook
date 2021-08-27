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
import { ImageComponent } from './components/image/image.component';
import { PaginatorComponent } from './components/paginator/paginator.component';
import { RecipeItemComponent } from './components/recipe-item/recipe-item.component';
import { SkeletonRecipeItemComponent } from './components/recipe-item/skeleton/skeleton-recipe-item.component';
import { RecipeListComponent } from './components/recipe-list/recipe-list.component';
import { SkeletonComponent } from './components/skeleton/skeleton.component';
import { ConfigService } from './core/config/config.service';
import { InputFocusDirective } from './core/directives/input-focus.directive';
import { MaterialModule } from './core/material/material.module';
import { AmountPipe } from './core/pipes/amount.pipe';
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
import { InputDialogComponent } from './components/input-dialog/input-dialog.component';

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
    RecipeImageSliderComponent,
    VerifyEmailDialogComponent,
    ResetPasswordDialogComponent,
    SkeletonRecipeItemComponent,
    SkeletonPageRecipeComponent,
    ImageComponent,
    AmountPipe,
    SkeletonComponent,
    InputDialogComponent,
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
