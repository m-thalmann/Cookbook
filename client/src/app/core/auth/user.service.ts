import { Injectable } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { Router } from '@angular/router';
import { User } from '../api/ApiInterfaces';
import StorageNames from '../StorageNames';

@Injectable({
  providedIn: 'root',
})
export class UserService {
  private _user: User | null = null;
  private _token: string | null = null;

  constructor(private router: Router, private dialog: MatDialog) {
    this.loadToken();
  }

  /**
   * Stores the user and token
   *
   * @param user the user to store
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
  }

  /**
   * Loads the token and user from storage or cookies (if stored before)
   *
   * @returns the loaded token or null, if no token was found
   */
  private loadToken(): void {
    let token = null;

    if (this.isRemembered) {
      token = localStorage.getItem(StorageNames.Token);
    } else {
      let token_cookie = document.cookie
        .split('; ')
        .map((el) => el.split('='))
        .find((el) => el[0] == StorageNames.Token);

      if (token_cookie != null) {
        token = token_cookie[1];
      }
    }

    if (token) {
      try {
        let user: User = UserService.parseUserFromToken(token);

        this._user = user;

        this._token = token;
      } catch (e) {
        console.error(e);

        if (this.isLoggedin) {
          this.logoutReason('badToken');
        } else {
          this.remove();
        }
      }
    }
  }

  /**
   * Removes the stored user and token from storage and cookies
   */
  private remove() {
    Object.values(StorageNames).forEach((storageName) => localStorage.removeItem(storageName));

    document.cookie = `${StorageNames.Token}=; expires=Thu, 01 Jan 1970 00:00:00 UTC`;

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
   * Whether the user is loggedin
   */
  get isLoggedin() {
    return this.token != null;
  }

  /**
   * Logsout the user and removes him from storage and cookies
   *
   * @param redirect_path the path to redirect the user to, after the logout (or no redirect if null)
   */
  logout(redirect_path: string | null = '/home') {
    this.remove();

    this.dialog.closeAll();

    if (redirect_path !== null) this.router.navigateByUrl(redirect_path);
  }

  /**
   * Logsout the user, removes him from storage and cookies and redirects him to the login page with a set reason
   *
   * @param reason the reason for the logout
   */
  logoutReason(reason: string) {
    this.logout(`/home?reason=${reason}`);
  }

  /**
   * Parses the user from a jwt token
   *
   * @throws If the token is not formatted correctly
   * @param token The jwt token
   * @returns The parsed user
   */
  static parseUserFromToken(token: string): User | never {
    let token_payload = JSON.parse(atob(token.split('.')[1]));

    if (!(typeof token_payload.user_id === 'number' && typeof token_payload.user_email === 'string')) {
      throw new Error('Token does not contain correct user data');
    }

    return {
      id: token_payload.user_id,
      email: token_payload.user_email,
    };
  }
}
