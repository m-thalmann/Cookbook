import { Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { ReplaySubject } from 'rxjs';
import { DetailedUser } from '../models/user';
import { StorageService } from '../services/storage.service';

const ACCESS_TOKEN_KEY = 'ACCESS_TOKEN';
const REFRESH_TOKEN_KEY = 'REFRESH_TOKEN';
const USER_KEY = 'USER';

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private _accessToken: string | null;
  private _refreshToken: string | null;

  private _user$ = new ReplaySubject<DetailedUser | null>();

  private _isAuthenticated: boolean;

  constructor(private storage: StorageService, private router: Router) {
    this._accessToken = this.storage.session.get<string>(ACCESS_TOKEN_KEY);
    this._refreshToken = this.storage.local.get(REFRESH_TOKEN_KEY);

    const user = this.storage.local.get<DetailedUser>(USER_KEY);

    this._user$.next(user);

    this._isAuthenticated = user !== null;
  }

  get user$() {
    return this._user$.asObservable();
  }

  get isAuthenticated() {
    return this._isAuthenticated;
  }

  get accessToken() {
    return this._accessToken;
  }

  private set accessToken(accessToken: string | null) {
    this._accessToken = accessToken;

    if (accessToken !== null) {
      this.storage.session.set(ACCESS_TOKEN_KEY, accessToken);
    } else {
      this.storage.session.remove(ACCESS_TOKEN_KEY);
    }
  }

  get refreshToken() {
    return this._refreshToken;
  }

  private set refreshToken(refreshToken: string | null) {
    this._refreshToken = refreshToken;

    if (refreshToken !== null) {
      this.storage.local.set(REFRESH_TOKEN_KEY, refreshToken);
    } else {
      this.storage.local.remove(REFRESH_TOKEN_KEY);
    }
  }

  public setUser(user: DetailedUser | null) {
    this._user$.next(user);
    this._isAuthenticated = user !== null;

    if (user !== null) {
      this.storage.local.set(USER_KEY, user);
    } else {
      this.storage.local.remove(USER_KEY);
    }
  }

  public async login(user: DetailedUser, accessToken: string, refreshToken: string) {
    this.setUser(user);

    this.accessToken = accessToken;
    this.refreshToken = refreshToken;

    this.router.navigate(['/dashboard']);
  }

  public logout() {
    this.accessToken = null;
    this.refreshToken = null;

    this.setUser(null);

    this.router.navigate(['/dashboard']);
  }

  public refreshAccessToken(accessToken: string, refreshToken: string) {
    this.accessToken = accessToken;
    this.refreshToken = refreshToken;
  }
}
