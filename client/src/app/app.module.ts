import { HttpClientModule } from '@angular/common/http';
import { APP_INITIALIZER, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { ComponentsModule } from './components/components.module';
import { ConfigService } from './core/config/config.service';
import { CoreModule } from './core/core.module';
import { TranslationService } from './core/i18n/translation.service';
import { ResetPasswordDialogComponent } from './layout/components/login-register-dialog/components/reset-password-dialog/reset-password-dialog.component';
import { VerifyEmailDialogComponent } from './layout/components/login-register-dialog/components/verify-email-dialog/verify-email-dialog.component';
import { LoginRegisterDialogComponent } from './layout/components/login-register-dialog/login-register-dialog.component';
import { SettingsDialogComponent } from './layout/components/settings-dialog/settings-dialog.component';
import { LayoutComponent } from './layout/layout.component';
import { LogoutComponent } from './pages/logout/logout.component';
import { PageCategoriesComponent } from './pages/page-categories/page-categories.component';
import { PageHomeComponent } from './pages/page-home/page-home.component';
import { PageMyRecipesComponent } from './pages/page-my-recipes/page-my-recipes.component';
import { PageNotFoundComponent } from './pages/page-not-found/page-not-found.component';
import { PageSearchComponent } from './pages/page-search/page-search.component';

export function setupServices(config: ConfigService, translation: TranslationService) {
  return async () => {
    await config.load();
    await translation.init(config.get('language', null));
  };
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
    PageSearchComponent,
    PageCategoriesComponent,
    PageNotFoundComponent,
    VerifyEmailDialogComponent,
    ResetPasswordDialogComponent,
  ],
  imports: [BrowserModule, AppRoutingModule, BrowserAnimationsModule, HttpClientModule, ComponentsModule, CoreModule],
  providers: [
    {
      provide: APP_INITIALIZER,
      useFactory: setupServices,
      deps: [ConfigService, TranslationService],
      multi: true,
    },
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}
