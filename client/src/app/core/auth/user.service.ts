import { EventEmitter, Injectable } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Router } from '@angular/router';
import { AuthUser } from '../api/ApiInterfaces';
import { ApiResponse } from '../api/ApiResponse';
import { Logger, LoggerColor, removeCookie, setSessionCookie } from '../functions';
import { TranslationService } from '../i18n/translation.service';
import { SnackbarService } from '../services/snackbar.service';
import StorageNames from '../StorageNames';

@Injectable({
  providedIn: 'root',
})
export class UserService {
  private _user: AuthUser | null = null;
  private _token: string | null = null;

  readonly userChanged = new EventEmitter<void>();

  constructor(
    private router: Router,
    private dialog: MatDialog,
    private snackbar: SnackbarService,
    private translation: TranslationService
  ) {
    try {
      this.loadToken();
    } catch (e) {}

    try {
      this.loadUserFromStorage();
    } catch (e: any) {
      Logger.error('UserService', LoggerColor.orange, e.message);
    }
  }

  /**
   * Stores the token
   *
   * @throws If the token is not formatted correctly
   *
   * @param token the token of the user
   * @param user the authenticated user
   * @param save whether to store the token
   */
  login(token: string, user: AuthUser, save: boolean = false) {
    this.remove();

    if (save) {
      localStorage.setItem(StorageNames.Token, token);
      localStorage.setItem(StorageNames.User, JSON.stringify(user));
    } else {
      setSessionCookie(StorageNames.Token, token);
      setSessionCookie(StorageNames.User, JSON.stringify(user));
    }

    this._user = user;
    this._token = token;

    this.userChanged.emit();

    const languageCode = this.user?.languageCode;

    if (languageCode) {
      this.translation.use(languageCode);
    }
  }

  /**
   * Loads the token from storage or cookies (if stored before)
   */
  private loadToken(): void {
    let token = null;

    if (this.isRemembered) {
      token = localStorage.getItem(StorageNames.Token);
    } else {
      let tokenCookie = document.cookie
        .split('; ')
        .map((el) => el.split('='))
        .find((el) => el[0] == StorageNames.Token);

      if (tokenCookie != null) {
        token = tokenCookie[1];
      }
    }

    if (token) {
      this._token = token;
    }
  }

  /**
   * Loads the user from storage or cookies (if stored before)
   */
  private loadUserFromStorage() {
    let _user: string | null = null;

    if (this.isRemembered) {
      _user = localStorage.getItem(StorageNames.User);
    } else {
      let userCookie = document.cookie
        .split('; ')
        .map((el) => el.split('='))
        .find((el) => el[0] == StorageNames.User);

      if (userCookie != null) {
        _user = userCookie[1];
      }
    }

    if (_user) {
      let user = JSON.parse(_user);

      if (
        typeof user.id !== 'number' ||
        typeof user.email !== 'string' ||
        typeof user.name !== 'string' ||
        typeof user.languageCode !== 'string' ||
        typeof user.isAdmin !== 'boolean'
      ) {
        throw new Error('User from storage does not contain correct data');
      }

      this._user = user;
    }
  }

  /**
   * Loads the user by an API-Response-promise
   *
   * @see {@link ApiService.getAuthenticatedUser}
   * @see {@link AppModule.setupServices}
   *
   * @param userPromise
   */
  async loadUser(userPromise: Promise<ApiResponse<AuthUser>>) {
    let res = await userPromise;

    if (res.isOK() && res.value) {
      let userJson = JSON.stringify(res.value);

      if (JSON.stringify(this._user) !== userJson) {
        this._user = res.value;

        if (this.isRemembered) {
          localStorage.setItem(StorageNames.User, userJson);
        } else {
          setSessionCookie(StorageNames.User, userJson);
        }
      }
    } else if (!res.isUnauthorized()) {
      Logger.warn('UserService', LoggerColor.orange, 'Auth-user could not be loaded:', res.error);
    }
  }

  /**
   * Removes the stored user and token from storage and cookies
   */
  private remove() {
    localStorage.removeItem(StorageNames.Token);
    localStorage.removeItem(StorageNames.User);

    removeCookie(StorageNames.Token);
    removeCookie(StorageNames.User);

    this._user = this._token = null;
  }

  /**
   * The stored user
   */
  get user() {
    return this._user;
  }

  /**
   * The stored token
   */
  get token() {
    return this._token;
  }

  /**
   * Whether the token was stored (not in cookies)
   */
  get isRemembered() {
    return localStorage.getItem(StorageNames.Token) !== null;
  }

  /**
   * Whether the user is logged in
   */
  get isLoggedin() {
    return this.token != null;
  }

  /**
   * Logs out the user and removes him from storage and cookies
   *
   * @param reason the reason for the logout
   * @param redirectPath the path to redirect the user to, after the logout (or no redirect if null)
   */
  logout(reason: string | null = null, redirectPath: string | null = '/home') {
    this.remove();

    this.dialog.closeAll();

    if (redirectPath !== null) this.router.navigateByUrl(redirectPath);

    switch (reason) {
      case 'logout':
        reason = 'messages.auth.logout.logout';
        break;
      case 'settings_changed':
      case 'unauthorized':
        reason = 'messages.auth.logout.log_back_in';
        break;
      case 'accountDeleted':
        reason = 'messages.auth.logout.account_deleted';
        break;
      default:
        reason = 'messages.auth.logout.logged_out';
    }

    if (reason) {
      this.snackbar.info(reason);
    }

    this.userChanged.emit();
  }
}
