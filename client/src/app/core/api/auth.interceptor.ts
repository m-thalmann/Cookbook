import {
  HttpContextToken,
  HttpErrorResponse,
  HttpEvent,
  HttpHandler,
  HttpInterceptor,
  HttpRequest,
} from '@angular/common/http';
import { Injectable } from '@angular/core';
import { catchError, finalize, first, from, map, Observable, of, shareReplay, switchMap, throwError } from 'rxjs';
import { AuthService } from '../auth/auth.service';
import { ApiService, TokenType } from './api.service';

export const TOKEN_TYPE_HTTP_CONTEXT = new HttpContextToken<TokenType | undefined>(() => undefined);
const REQUEST_REFRESH_TRIED_CONTEXT = new HttpContextToken<boolean>(() => false);

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private isRefreshing = false;
  private refreshedToken$?: Observable<string | null>;

  constructor(private api: ApiService, private auth: AuthService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const tokenType: TokenType | undefined = request.context.get(TOKEN_TYPE_HTTP_CONTEXT);
    const token = this.getTokenForRequest(tokenType);

    if (token === null && this.isAccessTokenType(tokenType) && this.auth.refreshToken !== null) {
      request.context.set(REQUEST_REFRESH_TRIED_CONTEXT, true);
      return this.refreshToken().pipe(
        catchError(() => of()),
        switchMap(() => this.intercept(request, next))
      );
    }

    request = this.appendTokenToRequest(request, token);

    return next.handle(request).pipe(
      catchError((error) => {
        if (
          !this.isUnauthorizedError(error) ||
          !this.isAccessTokenType(tokenType) ||
          request.context.get(REQUEST_REFRESH_TRIED_CONTEXT)
        ) {
          return throwError(() => error);
        }

        // retry the request if the token has changed in the time since the request
        if (token && this.getTokenForRequest(tokenType) !== token) {
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
  }

  private isAccessTokenType(tokenType?: TokenType) {
    return !!tokenType && [TokenType.Access, TokenType.AccessOptional].includes(tokenType);
  }

  private getTokenForRequest(tokenType: TokenType | undefined) {
    if (tokenType !== undefined) {
      if (tokenType === TokenType.Access || tokenType === TokenType.AccessOptional) {
        return this.auth.accessToken;
      } else if (tokenType === TokenType.Refresh) {
        return this.auth.refreshToken;
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
    if (this.auth.refreshToken === null) {
      return this.logoutAndThrowError(new Error('No refresh token set'));
    }

    if (!this.isRefreshing) {
      this.isRefreshing = true;
      this.refreshedToken$ = this.api.auth.refreshToken().pipe(
        catchError((error) => {
          if (this.isUnauthorizedError(error)) {
            return this.logoutAndThrowError(error);
          }

          return throwError(() => error);
        }),
        finalize(() => {
          this.isRefreshing = false;
        }),
        map((response) => {
          const refreshResponse = response.body!.data;

          this.auth.refreshAccessToken(refreshResponse.access_token, refreshResponse.refresh_token);
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

  private isUnauthorizedError(error: any) {
    return error instanceof HttpErrorResponse && error.status === 401;
  }

  private logoutAndThrowError(error: any) {
    return from(this.auth.logout(false)).pipe(switchMap(() => throwError(() => error)));
  }
}
