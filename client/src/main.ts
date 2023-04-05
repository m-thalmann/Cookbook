import { APP_INITIALIZER, Injectable, enableProdMode, importProvidersFrom, isDevMode } from '@angular/core';

import { HTTP_INTERCEPTORS, provideHttpClient, withInterceptorsFromDi } from '@angular/common/http';
import {
  BrowserModule,
  HAMMER_GESTURE_CONFIG,
  HammerGestureConfig,
  HammerModule,
  bootstrapApplication,
} from '@angular/platform-browser';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { provideRouter } from '@angular/router';
import { AppComponent } from './app/app.component';
import { environment } from './environments/environment';

import { AuthInterceptor } from './app/core/api/auth.interceptor';
import { AuthService } from './app/core/auth/auth.service';
import { ConfigService } from './app/core/services/config.service';
import { ThemeService } from './app/core/services/theme.service';

import routes from './app/routes';
import { ServiceWorkerModule } from '@angular/service-worker';
import { MatSnackBarModule } from '@angular/material/snack-bar';
import { MatDialogModule } from '@angular/material/dialog';

if (environment.production) {
  enableProdMode();
}

function setup(config: ConfigService, auth: AuthService) {
  return async () => {
    await config.load();

    auth.initialize();
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
    provideRouter(routes),
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
    {
      provide: APP_INITIALIZER,
      useFactory: setup,
      deps: [ConfigService, AuthService],
      multi: true,
    },
    {
      provide: APP_INITIALIZER,
      useFactory: loadTheme,
      deps: [ThemeService],
      multi: true,
    },
  ],
});
