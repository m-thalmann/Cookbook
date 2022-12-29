import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { APP_INITIALIZER, NgModule, isDevMode, Injectable } from '@angular/core';
import { MatIconRegistry } from '@angular/material/icon';
import { BrowserModule, HammerGestureConfig, HAMMER_GESTURE_CONFIG } from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { HammerModule } from '../../node_modules/@angular/platform-browser';
import { AppRoutingModule } from './app-routing.module';
import { ServiceWorkerModule } from '@angular/service-worker';

import { AuthInterceptor } from './core/api/auth.interceptor';
import { AuthService } from './core/auth/auth.service';
import { ConfigService } from './core/services/config.service';

import { AppComponent } from './app.component';
import { ComponentsModule } from './components/components.module';
import { CoreModule } from './core/core.module';
import { LayoutAuthComponent } from './layouts/layout-auth/layout-auth.component';
import { AccountMenuBottomSheetComponent } from './layouts/layout-default/components/account-menu-bottom-sheet/account-menu-bottom-sheet.component';
import { LayoutDefaultNavbarComponent } from './layouts/layout-default/components/layout-default-navbar/layout-default-navbar.component';
import { LayoutDefaultComponent } from './layouts/layout-default/layout-default.component';
import { LoginPageComponent } from './pages/auth/login-page/login-page.component';
import { SignUpPageComponent } from './pages/auth/sign-up-page/sign-up-page.component';
import { HomePageSectionComponent } from './pages/home-page/components/home-page-section/home-page-section.component';
import { HomePageComponent } from './pages/home-page/home-page.component';
import { CookbooksPageComponent } from './pages/cookbooks/cookbooks-page/cookbooks-page.component';

function setup(config: ConfigService, auth: AuthService) {
  return async () => {
    await config.load();

    auth.initialize();
  };
}

@Injectable({
  providedIn: 'root',
})
class CustomHammerConfig extends HammerGestureConfig {
  override overrides = {
    swipe: { direction: 2 | 4 | 8 | 16 }, // all directions
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
    HomePageSectionComponent,
    CookbooksPageComponent,
  ],
  imports: [
    BrowserModule,
    BrowserAnimationsModule,
    AppRoutingModule,
    HttpClientModule,
    HammerModule,
    ComponentsModule,
    CoreModule,
    ServiceWorkerModule.register('ngsw-worker.js', {
      enabled: !isDevMode(),
      // Register the ServiceWorker as soon as the application is stable
      // or after 30 seconds (whichever comes first).
      registrationStrategy: 'registerWhenStable:30000',
    }),
  ],
  providers: [
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true,
    },
    {
      provide: HAMMER_GESTURE_CONFIG,
      useClass: CustomHammerConfig,
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
