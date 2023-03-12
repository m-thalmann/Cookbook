import { HttpErrorResponse } from '@angular/common/http';
import { Injectable, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { BehaviorSubject, distinctUntilChanged, lastValueFrom, map, Observable, Subscription } from 'rxjs';
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
export class AuthService implements OnDestroy {
  private subSink = new Subscription();

  private _isInitialized$ = new BehaviorSubject<boolean>(false);
  isInitialized$ = this._isInitialized$.asObservable();

  private _accessToken$: BehaviorSubject<string | null>;
  private _refreshToken$: BehaviorSubject<string | null>;
  accessToken$: Observable<string | null>;
  refreshToken$: Observable<string | null>;

  private _user$: BehaviorSubject<DetailedUser | null>;
  user$: Observable<DetailedUser | null>;

  isAuthenticated$: Observable<boolean>;

  constructor(
    private storage: StorageService,
    private router: Router,
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private routeHelper: RouteHelperService
  ) {
    this._accessToken$ = new BehaviorSubject(this.storage.session.get<string>(ACCESS_TOKEN_KEY));
    this._refreshToken$ = new BehaviorSubject(this.storage.local.get(REFRESH_TOKEN_KEY));

    this.accessToken$ = this._accessToken$.asObservable().pipe(distinctUntilChanged());
    this.refreshToken$ = this._refreshToken$.asObservable().pipe(distinctUntilChanged());

    this.subSink.add(
      this.accessToken$.subscribe((accessToken) => {
        if (accessToken !== null) {
          this.storage.session.set(ACCESS_TOKEN_KEY, accessToken);
        } else {
          this.storage.session.remove(ACCESS_TOKEN_KEY);
        }
      })
    );

    this.subSink.add(
      this.refreshToken$.subscribe((refreshToken) => {
        if (refreshToken !== null) {
          this.storage.local.set(REFRESH_TOKEN_KEY, refreshToken);
        } else {
          this.storage.local.remove(REFRESH_TOKEN_KEY);
        }
      })
    );

    this._user$ = new BehaviorSubject<DetailedUser | null>(this.storage.local.get<DetailedUser>(USER_KEY));
    this.user$ = this._user$
      .asObservable()
      .pipe(distinctUntilChanged((previous, current) => JSON.stringify(previous) === JSON.stringify(current)));

    this.subSink.add(
      this.user$.subscribe((user) => {
        if (user !== null) {
          this.storage.local.set(USER_KEY, user);
        } else {
          this.storage.local.remove(USER_KEY);
        }
      })
    );

    this.isAuthenticated$ = this._user$.pipe(
      map((user) => user !== null),
      distinctUntilChanged()
    );
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }

  async initialize() {
    if (this._refreshToken$.value === null) {
      this._accessToken$.next(null);
      this._user$.next(null);

      return;
    }

    try {
      const userResponse = await lastValueFrom(this.api.auth.getAuthenticatedUser());

      this._user$.next(userResponse.body!.data);
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

  public async login(user: DetailedUser, accessToken: string, refreshToken: string, redirectUrl = '/home') {
    this._user$.next(user);

    this._accessToken$.next(accessToken);
    this._refreshToken$.next(refreshToken);

    this.router.navigateByUrl(redirectUrl);
  }

  public async logout(logoutFromApi: boolean) {
    if (logoutFromApi) {
      try {
        await lastValueFrom(this.api.auth.logout());
      } catch (e) {}
    }

    this._accessToken$.next(null);
    this._refreshToken$.next(null);

    this._user$.next(null);

    if (this.routeHelper.routeContainsGuard(this.activatedRoute, AuthGuard)) {
      await this.router.navigate(['/home']);
    }
  }

  public refreshAccessToken(accessToken: string, refreshToken: string) {
    this._accessToken$.next(accessToken);
    this._refreshToken$.next(refreshToken);
  }
}
