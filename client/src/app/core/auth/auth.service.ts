import { HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { BehaviorSubject, distinctUntilChanged, lastValueFrom, map } from 'rxjs';
import { ApiService } from '../api/api.service';
import { Logger as LoggerClass } from '../helpers/logger';
import { DetailedUser } from '../models/user';
import { RouteHelperService } from '../services/route-helper.service';
import { StorageService } from '../services/storage.service';
import { AuthGuard } from './auth.guard';

const ACCESS_TOKEN_KEY = 'ACCESS_TOKEN';
const REFRESH_TOKEN_KEY = 'REFRESH_TOKEN';
const USER_KEY = 'USER';

const Logger = new LoggerClass('AuthService');

@Injectable({
  providedIn: 'root',
})
export class AuthService {
  private _isInitialized$ = new BehaviorSubject<boolean>(false);

  private _accessToken: string | null;
  private _refreshToken: string | null;

  private _user$ = new BehaviorSubject<DetailedUser | null>(null);

  isAuthenticated$ = this._user$.pipe(
    map((user) => user !== null),
    distinctUntilChanged()
  );

  constructor(
    private storage: StorageService,
    private router: Router,
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private routeHelper: RouteHelperService
  ) {
    this._accessToken = this.storage.session.get<string>(ACCESS_TOKEN_KEY);
    this._refreshToken = this.storage.local.get(REFRESH_TOKEN_KEY);

    const user = this.storage.local.get<DetailedUser>(USER_KEY);

    this._user$.next(user);
  }

  get isInitialized$() {
    return this._isInitialized$.asObservable();
  }

  async initialize() {
    if (this.refreshToken === null) {
      this.accessToken = null;
      this.setUser(null);

      return;
    }

    try {
      const userResponse = await lastValueFrom(this.api.auth.getAuthenticatedUser());

      this.setUser(userResponse.body!.data);
    } catch (e) {
      await this.logout(false);

      let error = e;

      if (e instanceof HttpErrorResponse) {
        error = e.error.message;
      }

      Logger.info('User logged out due to the following error:', error);
    }

    this._isInitialized$.next(true);
  }

  get user$() {
    return this._user$.asObservable();
  }

  get isAuthenticated() {
    return this._user$.getValue() !== null;
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
    if (JSON.stringify(user) !== JSON.stringify(this._user$.value)) {
      this._user$.next(user);
    }

    if (user !== null) {
      this.storage.local.set(USER_KEY, user);
    } else {
      this.storage.local.remove(USER_KEY);
    }
  }

  public async login(user: DetailedUser, accessToken: string, refreshToken: string, redirectUrl = '/home') {
    this.setUser(user);

    this.accessToken = accessToken;
    this.refreshToken = refreshToken;

    this.router.navigateByUrl(redirectUrl);
  }

  public async logout(logoutFromApi: boolean) {
    if (logoutFromApi) {
      try {
        await lastValueFrom(this.api.auth.logout());
      } catch (e) {}
    }

    this.accessToken = null;
    this.refreshToken = null;

    this.setUser(null);

    const currentRouteConfig = this.routeHelper.getRouteLeaf(this.activatedRoute)?.routeConfig;

    if (currentRouteConfig?.canActivate?.[0] === AuthGuard) {
      this.router.navigate(['/home']);
    }
  }

  public refreshAccessToken(accessToken: string, refreshToken: string) {
    this.accessToken = accessToken;
    this.refreshToken = refreshToken;
  }
}
