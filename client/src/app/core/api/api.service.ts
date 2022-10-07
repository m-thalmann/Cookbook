import {
  HttpClient,
  HttpContext,
  HttpErrorResponse,
  HttpHeaders,
  HttpParams,
  HttpRequest,
  HttpResponse,
} from '@angular/common/http';
import { Injectable } from '@angular/core';
import { filter, Observable, shareReplay } from 'rxjs';
import { DetailedUser } from '../models/user';
import { TOKEN_TYPE_HTTP_CONTEXT } from './auth.interceptor';

export enum TokenType {
  Access,
  Refresh,
  None,
}

export interface SignedRoute {
  signature: string;
  expires: string;
}

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  constructor(private http: HttpClient) {}

  public get url() {
    return 'http://localhost:8000/v1'; // TODO: add url from config
  }

  private get httpHeaders() {
    // TODO: add language header

    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
    });

    return headers;
  }

  private request<T>(request: HttpRequest<T>, tokenType?: TokenType) {
    let headers = this.httpHeaders;

    request = request.clone({
      url: `${this.url}${request.url}`,
      headers: headers,
      context: new HttpContext().set(TOKEN_TYPE_HTTP_CONTEXT, tokenType),
    });

    return this.http.request(request).pipe(
      shareReplay(1),
      filter((response) => response instanceof HttpResponse)
    ) as Observable<HttpResponse<T>>;
  }

  public get<T>(path: string, tokenType: TokenType, params?: HttpParams) {
    return this.request(new HttpRequest<T>('GET', path, { params: params }), tokenType);
  }

  public post<T>(path: string, body: any, tokenType: TokenType, params?: HttpParams) {
    return this.request(new HttpRequest<T>('POST', path, body, { params: params }), tokenType);
  }

  public put<T>(path: string, body: any, tokenType: TokenType, params?: HttpParams) {
    return this.request(new HttpRequest<T>('PUT', path, body, { params: params }), tokenType);
  }

  public delete<T>(path: string, tokenType: TokenType, params?: HttpParams) {
    return this.request(new HttpRequest<T>('DELETE', path, { params: params }), tokenType);
  }

  public static getErrorMessage(error: HttpErrorResponse) {
    if (error.status === 422) {
      return error.error.errors[Object.keys(error.error.errors)[0]][0];
    } else {
      return error.error.message;
    }
  }

  private static generateParams(params: { [key: string]: string | undefined }) {
    for (const key in params) {
      if (params[key] === undefined) {
        delete params[key];
      }
    }

    return new HttpParams().appendAll(params as { [key: string]: string });
  }

  private static generateSignedRouteParams(signature: SignedRoute) {
    return new HttpParams().append('signature', signature.signature).append('expires', signature.expires);
  }

  /*
  |---------------|
  |  API Methods  |
  |---------------|
  */

  public get auth() {
    return {
      getAuthenticatedUser: () => this.get<{ data: DetailedUser }>('/auth', TokenType.Access),

      login: (email: string, password: string) =>
        this.post<{ data: { user: DetailedUser; access_token: string; refresh_token: string } }>(
          '/auth/login',
          { email, password },
          TokenType.None
        ),

      signUp: (
        data: { name: string; email: string; password: string; language_code?: string },
        hcaptchaToken?: string
      ) => {
        const signUpData = {
          ...data,
          hcaptcha_token: hcaptchaToken,
        };

        return this.post<{ data: { user: DetailedUser; access_token: string; refresh_token: string } }>(
          '/auth/register',
          signUpData,
          TokenType.None
        );
      },

      logout: () => this.post<void>('/auth/logout', {}, TokenType.Access),

      refreshToken: () =>
        this.post<{ data: { access_token: string; refresh_token: string } }>('/auth/refresh', {}, TokenType.Refresh),
    };
  }

  public get categories() {
    return {
      getList: (all = false) =>
        this.get<{ data: string[] }>(
          '/categories',
          TokenType.Access,
          ApiService.generateParams({ all: all ? '' : undefined })
        ),
    };
  }
}

