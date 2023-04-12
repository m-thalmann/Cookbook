import {
  HttpContextToken,
  HttpErrorResponse,
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
} from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Observable, catchError, combineLatest, first, from, map, shareReplay, switchMap, throwError } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { ApiService, TokenType } from './api.service';

export const TOKEN_TYPE_HTTP_CONTEXT = new HttpContextToken<TokenType | undefined>(() => undefined);
const REQUEST_REFRESH_TRIED_CONTEXT = new HttpContextToken<boolean>(() => false);

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private isRefreshing = false;
  private refreshedToken$?: Observable<string | null>;

  private authTokens$ = combineLatest([this.auth.accessToken$, this.auth.refreshToken$]).pipe(
    map(([accessToken, refreshToken]) => ({ accessToken, refreshToken }))
  );

  constructor(private api: ApiService, private auth: AuthService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    if (!request.url.startsWith(this.api.url)) {
      return next.handle(request);
    }

    const tokenType: TokenType | undefined = request.context.get(TOKEN_TYPE_HTTP_CONTEXT);

    if (this.isRefreshing && this.refreshedToken$ && tokenType !== TokenType.Refresh) {
      return this.interceptAfterRefresh(this.refreshedToken$, request, next);
    }

    return this.authTokens$.pipe(
      first(),
      switchMap(({ accessToken, refreshToken }) => {
        const token = this.getTokenForRequest(tokenType, { accessToken, refreshToken });

        if (token === null && this.isAccessTokenType(tokenType) && refreshToken !== null) {
          return this.interceptAfterRefresh(this.refreshToken(), request, next);
        }

        if (token === null && tokenType === TokenType.Refresh) {
          return this.logoutAndThrowError(new Error('No refresh token set'));
        }

        request = this.appendTokenToRequest(request, token);

        return next.handle(request).pipe(
          catchError((error) => {
            if (
              !this.isUnauthorizedError(error) ||
              this.isUnverifiedError(error) ||
              !this.isAccessTokenType(tokenType) ||
              request.context.get(REQUEST_REFRESH_TRIED_CONTEXT)
            ) {
              return throwError(() => error);
            }

            // retry the request if the token has changed in the time since the request
            if (token && this.getTokenForRequest(tokenType, { accessToken, refreshToken }) !== token) {
              return this.intercept(request, next);
            }

            request.context.set(REQUEST_REFRESH_TRIED_CONTEXT, true);

            return this.refreshToken().pipe(
              catchError(() => {
                if (tokenType === TokenType.AccessOptional) {
                  request.context.set(TOKEN_TYPE_HTTP_CONTEXT, TokenType.None);

                  return this.intercept(request, next);
                }

                return throwError(() => error);
              }),
              switchMap(() => this.intercept(request, next))
            );
          })
        );
      })
    );
  }

  private isAccessTokenType(tokenType?: TokenType) {
    return tokenType !== undefined && [TokenType.Access, TokenType.AccessOptional].includes(tokenType);
  }

  private getTokenForRequest(
    tokenType: TokenType | undefined,
    tokens: { accessToken: string | null; refreshToken: string | null }
  ) {
    if (tokenType !== undefined) {
      if (this.isAccessTokenType(tokenType)) {
        return tokens.accessToken;
      } else if (tokenType === TokenType.Refresh) {
        return tokens.refreshToken;
      }
    }

    return null;
  }

  private appendTokenToRequest(request: HttpRequest<unknown>, token: string | null) {
    if (token) {
      return request.clone({
        setHeaders: {
          Authorization: `Bearer ${token}`,
        },
      });
    }

    return request;
  }

  private refreshToken(): Observable<void> {
    if (!this.isRefreshing) {
      this.isRefreshing = true;
      this.refreshedToken$ = this.api.auth.refreshToken().pipe(
        catchError((error) => {
          this.isRefreshing = false;

          if (this.isUnauthorizedError(error)) {
            return this.logoutAndThrowError(error);
          }

          return throwError(() => error);
        }),
        map((response) => {
          const refreshResponse = response.body!.data;

          this.auth.refreshAccessToken(refreshResponse.access_token, refreshResponse.refresh_token);

          this.isRefreshing = false;

          return refreshResponse.access_token;
        }),
        shareReplay(1)
      );
    }

    if (!this.refreshedToken$) {
      this.isRefreshing = false;
      return this.refreshToken();
    }

    return this.refreshedToken$.pipe(
      first(),
      map(() => undefined)
    );
  }

  private interceptAfterRefresh(refresh: Observable<any>, request: HttpRequest<unknown>, next: HttpHandler) {
    request.context.set(REQUEST_REFRESH_TRIED_CONTEXT, true);

    return refresh.pipe(
      first(),
      switchMap(() => this.intercept(request, next))
    );
  }

  private isUnauthorizedError(error: any) {
    return error instanceof HttpErrorResponse && error.status === 401;
  }

  private isUnverifiedError(error: any) {
    return error instanceof HttpErrorResponse && error.status === 401 && error.error.reason === 'unverified';
  }

  /**
   * __Info:__ This will not logout when the error is an "unverified error" from the api.
   */
  private logoutAndThrowError(error: any) {
    if (this.isUnverifiedError(error)) {
      return throwError(() => error);
    }

    return from(this.auth.logout(false)).pipe(switchMap(() => throwError(() => error)));
  }
}
