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
import { CookbookWithCounts } from '../models/cookbook';
import { FilterOptions } from '../models/filter-options';
import { PaginationMeta } from '../models/pagination-meta';
import { PaginationOptions } from '../models/pagination-options';
import { DetailedRecipe, EditRecipeData, ListRecipe } from '../models/recipe';
import { SortOption } from '../models/sort-option';
import { DetailedUser } from '../models/user';
import { ConfigService } from '../services/config.service';
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
  constructor(private http: HttpClient, private config: ConfigService) {}

  public get url() {
    return this.config.get('API_URL');
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

  public static getErrorMessage(error: unknown) {
    if (error instanceof HttpErrorResponse) {
      if (error.status === 422) {
        return error.error.errors[Object.keys(error.error.errors)[0]][0];
      } else {
        return error.error.message;
      }
    }

    return error;
  }

  private static generateParams(params: { [key: string]: string | undefined }) {
    for (const key in params) {
      if (params[key] === undefined) {
        delete params[key];
      }
    }

    return new HttpParams().appendAll(params as { [key: string]: string });
  }

  private static mergeParams(...httpParams: (HttpParams | null)[]) {
    let mergedParams: { [key: string]: string[] } = {};

    for (let params of httpParams) {
      if (params) {
        for (let param of params.keys()) {
          mergedParams[param] = params.getAll(param)!;
        }
      }
    }

    if (Object.keys(mergedParams).length === 0) {
      return undefined;
    }

    return new HttpParams().appendAll(mergedParams);
  }

  private static generateSignedRouteParams(signature: SignedRoute) {
    return new HttpParams().append('signature', signature.signature).append('expires', signature.expires);
  }

  private static generatePaginationParams(pagination?: PaginationOptions) {
    if (!pagination) {
      return null;
    }

    const paginationParams = {
      page: pagination.page?.toString(),
      per_page: pagination.perPage?.toString(),
    };

    return ApiService.generateParams(paginationParams);
  }

  private static generateSortParams(sort?: SortOption[]) {
    if (!sort) {
      return null;
    }

    const sortString = sort
      .map((option) => {
        const dirPrefix = option.dir === 'desc' ? '-' : '';

        return dirPrefix + option.column;
      })
      .join(',');

    return new HttpParams().append('sort', sortString);
  }

  private static generateFilterParams(filter?: FilterOptions) {
    if (!filter) {
      return null;
    }

    return null; // TODO:
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
          '/auth/sign-up',
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

  public get recipes() {
    return {
      getList: (
        all?: boolean,
        pagination?: PaginationOptions,
        sort?: SortOption[],
        search?: string,
        filter?: FilterOptions
      ) => {
        const baseParams = ApiService.generateParams({ all: all ? '' : undefined, search });
        const paginationParams = ApiService.generatePaginationParams(pagination);
        const sortParams = ApiService.generateSortParams(sort);
        const filterParams = ApiService.generateFilterParams(filter);

        return this.get<{ data: ListRecipe[]; meta: PaginationMeta }>(
          '/recipes',
          TokenType.Access,
          ApiService.mergeParams(baseParams, paginationParams, sortParams, filterParams)
        );
      },

      get: (id: number) => this.get<{ data: DetailedRecipe }>(`/recipes/${id}`, TokenType.Access),
      getShared: (shareUuid: string) =>
        this.get<{ data: DetailedRecipe }>(`/recipes/shared/${shareUuid}`, TokenType.None),

      update: (id: number, data: EditRecipeData) =>
        this.put<{ data: DetailedRecipe }>(`/recipes/${id}`, data, TokenType.Access),

      delete: (id: number) => this.delete<void>(`/recipes/${id}`, TokenType.Access),
    };
  }

  public get cookbooks() {
    return {
      getList: (all?: boolean, pagination?: PaginationOptions, sort?: SortOption[], search?: string) => {
        const baseParams = ApiService.generateParams({ all: all ? '' : undefined, search });
        const paginationParams = ApiService.generatePaginationParams(pagination);
        const sortParams = ApiService.generateSortParams(sort);

        return this.get<{ data: CookbookWithCounts[]; meta: PaginationMeta }>(
          '/cookbooks',
          TokenType.Access,
          ApiService.mergeParams(baseParams, paginationParams, sortParams)
        );
      },
    };
  }
}
