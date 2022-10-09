import { APP_INITIALIZER, NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { MaterialModule } from './core/material/material.module';
import { LayoutDefaultComponent } from './layouts/layout-default/layout-default.component';
import { LoginPageComponent } from './pages/auth/login-page/login-page.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HomePageComponent } from './pages/home-page/home-page.component';
import { LayoutAuthComponent } from './layouts/layout-auth/layout-auth.component';
import { SignUpPageComponent } from './pages/auth/sign-up-page/sign-up-page.component';
import { MatIconRegistry } from '@angular/material/icon';
import { LayoutDefaultNavbarComponent } from './layouts/layout-default/components/layout-default-navbar/layout-default-navbar.component';
import { AccountMenuBottomSheetComponent } from './layouts/layout-default/components/account-menu-bottom-sheet/account-menu-bottom-sheet.component';
import { SearchBarComponent } from './components/search-bar/search-bar.component';
import { ClampArrayPipe } from './core/pipes/clamp-array.pipe';
import { HomePageSectionComponent } from './pages/home-page/components/home-page-section/home-page-section.component';
import { AuthInterceptor } from './core/api/auth.interceptor';
import { RecipeCardComponent } from './components/recipe-card/recipe-card.component';
import { ConfigService } from './core/services/config.service';
import { AuthService } from './core/auth/auth.service';
import { CookbookCardComponent } from './components/cookbook-card/cookbook-card.component';

function setup(config: ConfigService, auth: AuthService) {
  return async () => {
    await config.load();

    auth.initialize();
  };
}

@NgModule({
  declarations: [
    AppComponent,
    LayoutDefaultComponent,
    LoginPageComponent,
    HomePageComponent,
    LayoutAuthComponent,
    SignUpPageComponent,
    LayoutDefaultNavbarComponent,
    AccountMenuBottomSheetComponent,
    SearchBarComponent,
    ClampArrayPipe,
    HomePageSectionComponent,
    RecipeCardComponent,
    CookbookCardComponent,
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    BrowserAnimationsModule,
    MaterialModule,
    FormsModule,
    ReactiveFormsModule,
  ],
  providers: [
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true,
    },
    {
      provide: APP_INITIALIZER,
      useFactory: setup,
      deps: [ConfigService, AuthService],
      multi: true,
    },
  ],
  bootstrap: [AppComponent],
})
export class AppModule {
  constructor(iconRegistry: MatIconRegistry) {
    iconRegistry.setDefaultFontSetClass('material-icons-round');
  }
}
