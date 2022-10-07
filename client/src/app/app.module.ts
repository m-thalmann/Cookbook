import { NgModule } from '@angular/core';
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
import { AuthInterceptor } from './core/api/auth.interceptor';

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
  ],
  bootstrap: [AppComponent],
})
export class AppModule {
  constructor(iconRegistry: MatIconRegistry) {
    iconRegistry.setDefaultFontSetClass('material-icons-round');
  }
}
