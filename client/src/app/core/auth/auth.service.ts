import { HttpErrorResponse, HttpResponse } from '@angular/common/http';
import { Injectable, OnDestroy } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoService } from '@ngneat/transloco';
import {
  BehaviorSubject,
  Observable,
  ReplaySubject,
  Subscription,
  distinctUntilChanged,
  lastValueFrom,
  map,
  shareReplay,
} from 'rxjs';
import { ApiService } from '../api/api.service';
import { Logger as LoggerClass } from '../helpers/logger';
import { DetailedUser } from '../models/user';
import { RouteHelperService } from '../services/route-helper.service';
import { SnackbarService } from '../services/snackbar.service';
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

  private _isInitialized$ = new ReplaySubject<boolean>(1);
  isInitialized$ = this._isInitialized$.asObservable();

  private _accessToken$: BehaviorSubject<string | null>;
  private _refreshToken$: BehaviorSubject<string | null>;
  accessToken$: Observable<string | null>;
  refreshToken$: Observable<string | null>;

  private _user$: BehaviorSubject<DetailedUser | null>;
  user$: Observable<DetailedUser | null>;

  isAuthenticated$: Observable<boolean>;

  private _isUnverified$ = new BehaviorSubject(false);
  isUnverified$ = this._isUnverified$.asObservable();

  constructor(
    private storage: StorageService,
    private router: Router,
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private routeHelper: RouteHelperService,
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {
    this._accessToken$ = new BehaviorSubject(this.storage.get<string>(ACCESS_TOKEN_KEY));
    this._refreshToken$ = new BehaviorSubject(this.storage.get(REFRESH_TOKEN_KEY));

    this.accessToken$ = this._accessToken$.asObservable().pipe(distinctUntilChanged(), shareReplay(1));
    this.refreshToken$ = this._refreshToken$.asObservable().pipe(distinctUntilChanged(), shareReplay(1));

    this.subSink.add(
      this.accessToken$.subscribe((accessToken) => {
        if (accessToken !== null) {
          this.storage.set(ACCESS_TOKEN_KEY, accessToken);
        } else {
          this.storage.remove(ACCESS_TOKEN_KEY);
        }
      })
    );

    this.subSink.add(
      this.refreshToken$.subscribe((refreshToken) => {
        if (refreshToken !== null) {
          this.storage.set(REFRESH_TOKEN_KEY, refreshToken);
        } else {
          this.storage.remove(REFRESH_TOKEN_KEY);
        }
      })
    );

    this.subSink.add(
      this.storage.observe<string>(ACCESS_TOKEN_KEY).subscribe((accessToken) => this._accessToken$.next(accessToken))
    );
    this.subSink.add(
      this.storage
        .observe<string>(REFRESH_TOKEN_KEY)
        .subscribe((refreshToken) => this._refreshToken$.next(refreshToken))
    );

    this._user$ = new BehaviorSubject<DetailedUser | null>(this.storage.get<DetailedUser>(USER_KEY));
    this.user$ = this._user$.asObservable().pipe(
      distinctUntilChanged((previous, current) => JSON.stringify(previous) === JSON.stringify(current)),
      shareReplay(1)
    );

    this.subSink.add(
      this.user$.subscribe((user) => {
        if (user !== null) {
          this.storage.set(USER_KEY, user);
        } else {
          this.storage.remove(USER_KEY);
        }
      })
    );

    this.isAuthenticated$ = this._user$.pipe(
      map((user) => user !== null),
      distinctUntilChanged(),
      shareReplay(1)
    );
  }

  async initialize() {
    if (this._refreshToken$.value === null) {
      this._accessToken$.next(null);
      this._user$.next(null);
      this._isInitialized$.next(true);

      return;
    }

    try {
      const userResponse = await lastValueFrom(this.api.auth.getAuthenticatedUser());

      this._user$.next(userResponse.body!.data);

      this.setEmailVerified(userResponse);
    } catch (e) {
      if (!(e instanceof HttpErrorResponse) || e.status !== 401) {
        this.snackbar.warn('messages.errors.loadingUserInformation', { translateMessage: true });
        Logger.error('Error while initializing the user:', e);

        this._isInitialized$.next(false);

        return;
      }

      await this.logout(false);

      let error = this.api.getErrorMessage(e);

      Logger.info('User logged out due to the following error:', error);
    }

    this._isInitialized$.next(true);
  }

  public async login(user: DetailedUser, accessToken: string, refreshToken: string, redirectUrl = '/home') {
    this._accessToken$.next(accessToken);
    this._refreshToken$.next(refreshToken);

    this._user$.next(user);

    this.router.navigateByUrl(redirectUrl);
  }

  public async logout(logoutFromApi: boolean, message?: string) {
    if (message === undefined) {
      message = this.transloco.translate('messages.loggedOut')!;
    }

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

    this.snackbar.info(message, {});
  }

  public refreshAccessToken(accessToken: string, refreshToken: string) {
    this._accessToken$.next(accessToken);
    this._refreshToken$.next(refreshToken);
  }

  public updateUser(user: DetailedUser) {
    this._user$.next(user);
  }

  public setEmailVerified(verifiedOrApiResponse: boolean | HttpResponse<unknown>) {
    let unverified: boolean;

    if (typeof verifiedOrApiResponse === 'boolean') {
      unverified = !verifiedOrApiResponse;
    } else {
      unverified =
        verifiedOrApiResponse.headers.has('X-Unverified') &&
        verifiedOrApiResponse.headers.get('X-Unverified') === 'true';
    }

    this._isUnverified$.next(unverified);
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
