import {
  APP_INITIALIZER,
  ErrorHandler,
  Injectable,
  enableProdMode,
  importProvidersFrom,
  isDevMode,
} from '@angular/core';

import { HTTP_INTERCEPTORS, provideHttpClient, withInterceptorsFromDi } from '@angular/common/http';
import {
  BrowserModule,
  HAMMER_GESTURE_CONFIG,
  HammerGestureConfig,
  HammerModule,
  bootstrapApplication,
} from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { provideRouter, withInMemoryScrolling, withPreloading } from '@angular/router';
import { ServiceWorkerModule } from '@angular/service-worker';
import { environment } from './environments/environment';

import { AuthInterceptor } from './app/core/api/auth.interceptor';
import { AuthService } from './app/core/auth/auth.service';
import { ConfigService } from './app/core/services/config.service';
import { ErrorHandlerService } from './app/core/services/error-handler.service';
import { ThemeService } from './app/core/services/theme.service';

import { MatDialogModule } from '@angular/material/dialog';
import { MatSnackBarModule } from '@angular/material/snack-bar';

import { MatPaginatorIntl } from '@angular/material/paginator';
import { AppComponent } from './app/app.component';
import { LanguageService } from './app/core/language/language.service';
import { TranslocoRootModule } from './app/core/language/transloco-root.module';
import { CustomPaginatorIntl } from './app/core/material/custom-paginator-intl';
import { ConditionalPreloadStrategy } from './app/core/routes/conditional-preload-strategy';
import { SeoService } from './app/core/services/seo.service';
import routes from './app/routes';

if (environment.production) {
  enableProdMode();
}

function setup(language: LanguageService, config: ConfigService, auth: AuthService, seo: SeoService) {
  return async () => {
    language.init();

    await config.load();

    auth.initialize();
    seo.initialize();
  };
}

function loadTheme(theme: ThemeService) {
  return () => {
    theme.load();
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

bootstrapApplication(AppComponent, {
  providers: [
    provideRouter(
      routes,
      withInMemoryScrolling({ scrollPositionRestoration: 'enabled' }),
      withPreloading(ConditionalPreloadStrategy)
    ),
    provideHttpClient(withInterceptorsFromDi()),
    importProvidersFrom([
      BrowserModule,
      BrowserAnimationsModule,
      HammerModule,
      ServiceWorkerModule.register('ngsw-worker.js', {
        enabled: !isDevMode(),
        // Register the ServiceWorker as soon as the application is stable
        // or after 30 seconds (whichever comes first).
        registrationStrategy: 'registerWhenStable:30000',
      }),
      MatSnackBarModule,
      MatDialogModule,
      TranslocoRootModule,
    ]),
    {
      provide: HTTP_INTERCEPTORS,
      useClass: AuthInterceptor,
      multi: true,
    },
    {
      provide: HAMMER_GESTURE_CONFIG,
      useClass: CustomHammerConfig,
    },
    { provide: ErrorHandler, useClass: ErrorHandlerService },
    {
      provide: APP_INITIALIZER,
      useFactory: setup,
      deps: [LanguageService, ConfigService, AuthService, SeoService],
      multi: true,
    },
    {
      provide: APP_INITIALIZER,
      useFactory: loadTheme,
      deps: [ThemeService],
      multi: true,
    },
    { provide: MatPaginatorIntl, useClass: CustomPaginatorIntl },
  ],
});
