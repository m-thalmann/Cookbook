import { Injectable } from '@angular/core';
import {
  HttpRequest,
  HttpHandler,
  HttpEvent,
  HttpInterceptor,
  HttpContextToken,
  HttpErrorResponse,
} from '@angular/common/http';
import { BehaviorSubject, catchError, filter, finalize, first, Observable, switchMap, throwError } from 'rxjs';
import { ApiService, TokenType } from './api.service';
import { AuthService } from '../auth/auth.service';

export const TOKEN_TYPE_HTTP_CONTEXT = new HttpContextToken<TokenType | undefined>(() => undefined);

@Injectable()
export class AuthInterceptor implements HttpInterceptor {
  private isRefreshing = false;
  private refreshedToken$ = new BehaviorSubject<string | null>(null);

  constructor(private api: ApiService, private auth: AuthService) {}

  intercept(request: HttpRequest<unknown>, next: HttpHandler): Observable<HttpEvent<unknown>> {
    const tokenType: TokenType | undefined = request.context.get(TOKEN_TYPE_HTTP_CONTEXT);

    if (tokenType !== undefined) {
      let token: string | null = null;

      if (tokenType === TokenType.Access) {
        token = this.auth.accessToken;
      } else if (tokenType === TokenType.Refresh) {
        token = this.auth.refreshToken;
      }

      if (token) {
        request = request.clone({
          setHeaders: {
            Authorization: `Bearer ${token}`,
          },
        });
      }
    }

    return next.handle(request).pipe(
      catchError((error) => {
        if (error instanceof HttpErrorResponse && error.status === 401 && tokenType === TokenType.Access) {
          return this.handleUnauthorized(request, next).pipe(
            catchError(() => {
              this.auth.logout();

              return throwError(() => error);
            })
          );
        } else {
          return throwError(() => error);
        }
      })
    );
  }

  private handleUnauthorized(request: HttpRequest<unknown>, next: HttpHandler) {
    if (this.auth.refreshToken === null) {
      return throwError(() => null);
    }

    if (!this.isRefreshing) {
      this.isRefreshing = true;
      this.refreshedToken$.next(null);

      this.api.auth
        .refreshToken()
        .pipe(
          first(), // TODO: needs first? probably not
          catchError((error) => {
            this.refreshedToken$.error(error);
            this.auth.logout();

            return throwError(() => error);
          }),
          finalize(() => {
            this.isRefreshing = false; // TODO: test
          })
        )
        .subscribe((response) => {
          const refreshResponse = response.body!.data;

          this.auth.refreshAccessToken(refreshResponse.access_token, refreshResponse.refresh_token);
          this.refreshedToken$.next(refreshResponse.access_token);
        });
    }

    return this.refreshedToken$.pipe(
      filter((token) => token !== null),
      first(),
      switchMap((token) =>
        next.handle(
          request.clone({
            setHeaders: {
              Authorization: `Bearer ${token}`,
            },
          })
        )
      )
    );
  }
}

