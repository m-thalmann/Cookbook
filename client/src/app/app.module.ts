import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { APP_INITIALIZER, NgModule } from '@angular/core';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { MatIconRegistry } from '@angular/material/icon';
import { BrowserModule } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { HammerModule } from '../../node_modules/@angular/platform-browser';
import { AppRoutingModule } from './app-routing.module';
import { MaterialModule } from './core/material/material.module';

import { AuthInterceptor } from './core/api/auth.interceptor';
import { AuthService } from './core/auth/auth.service';
import { ClampArrayPipe } from './core/pipes/clamp-array.pipe';
import { ConfigService } from './core/services/config.service';

import { AppComponent } from './app.component';
import { CookbookCardComponent } from './components/cookbook-card/cookbook-card.component';
import { RecipeCardComponent } from './components/recipe-card/recipe-card.component';
import { SearchBarComponent } from './components/search-bar/search-bar.component';
import { LayoutAuthComponent } from './layouts/layout-auth/layout-auth.component';
import { AccountMenuBottomSheetComponent } from './layouts/layout-default/components/account-menu-bottom-sheet/account-menu-bottom-sheet.component';
import { LayoutDefaultNavbarComponent } from './layouts/layout-default/components/layout-default-navbar/layout-default-navbar.component';
import { LayoutDefaultComponent } from './layouts/layout-default/layout-default.component';
import { LoginPageComponent } from './pages/auth/login-page/login-page.component';
import { SignUpPageComponent } from './pages/auth/sign-up-page/sign-up-page.component';
import { HomePageSectionComponent } from './pages/home-page/components/home-page-section/home-page-section.component';
import { HomePageComponent } from './pages/home-page/home-page.component';
import { RecipeDetailImageSliderComponent } from './pages/recipe-detail-page/components/recipe-detail-image-slider/recipe-detail-image-slider.component';
import { RecipeDetailComponent } from './pages/recipe-detail-page/components/recipe-detail/recipe-detail.component';
import { RecipeDetailPageComponent } from './pages/recipe-detail-page/recipe-detail-page.component';
import { NumberInputComponent } from './components/number-input/number-input.component';
import { AmountPipe } from './core/pipes/amount.pipe';
import { ShareMenuComponent } from './components/share-menu/share-menu.component';
import { RecipePublicShareDialogComponent } from './pages/recipe-detail-page/components/recipe-public-share-dialog/recipe-public-share-dialog.component';

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
    RecipeDetailPageComponent,
    RecipeDetailComponent,
    RecipeDetailImageSliderComponent,
    NumberInputComponent,
    AmountPipe,
    ShareMenuComponent,
    RecipePublicShareDialogComponent,
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    HttpClientModule,
    BrowserAnimationsModule,
    MaterialModule,
    FormsModule,
    ReactiveFormsModule,
    HammerModule,
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
