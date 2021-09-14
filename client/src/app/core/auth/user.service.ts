import { EventEmitter, Injectable } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Router } from '@angular/router';
import { AuthUser } from '../api/ApiInterfaces';
import { Logger, LoggerColor } from '../functions';
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
  }

  /**
   * Stores the token
   *
   * @throws If the token is not formatted correctly
   *
   * @param token the token of the user
   * @param save whether to store the token
   */
  login(token: string, save: boolean = false) {
    this.remove();

    if (save) {
      localStorage.setItem(StorageNames.Token, token);
    } else {
      document.cookie = `${StorageNames.Token}=${token}; samesite=strict; path=/`;
    }

    this.loadToken();

    this.userChanged.emit();

    const languageCode = this.user?.languageCode;

    if (languageCode) {
      this.translation.use(languageCode);
    }
  }

  /**
   * Loads the token and user from storage or cookies (if stored before)
   *
   * @throws If the token is not formatted correctly
   *
   * @returns the loaded token or null, if no token was found
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
      try {
        this._user = UserService.parseUserFromToken(token);

        this._token = token;
      } catch (e) {
        Logger.error('UserService', LoggerColor.lightblue, 'Error parsing token:', e);

        if (this.isLoggedin) {
          this.logout('badToken');
        } else {
          this.remove();
        }

        throw e;
      }
    }
  }

  /**
   * Removes the stored user and token from storage and cookies
   */
  private remove() {
    localStorage.removeItem(StorageNames.Token);

    document.cookie = `${StorageNames.Token}=; samesite=strict; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC`;

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
      case 'badToken':
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

  /**
   * Parses the user from a jwt token
   *
   * @param token The jwt token
   *
   * @throws If the token is not formatted correctly
   *
   * @returns The parsed user
   */
  static parseUserFromToken(token: string): AuthUser {
    let tokenPayload = JSON.parse(atob(token.split('.')[1]));

    if (
      !(
        typeof tokenPayload.user_id === 'number' &&
        typeof tokenPayload.user_email === 'string' &&
        typeof tokenPayload.user_name === 'string' &&
        typeof tokenPayload.user_languageCode === 'string' &&
        typeof tokenPayload.user_isAdmin === 'boolean'
      )
    ) {
      throw new Error('Token does not contain correct user data');
    }

    return {
      id: tokenPayload.user_id,
      email: tokenPayload.user_email,
      name: tokenPayload.user_name,
      languageCode: tokenPayload.user_languageCode,
      isAdmin: tokenPayload.user_isAdmin,
    };
  }
}
