import {
  HttpClient,
  HttpContext,
  HttpErrorResponse,
  HttpEventType,
  HttpHeaders,
  HttpParams,
  HttpRequest,
  HttpResponse,
  HttpStatusCode,
} from '@angular/common/http';
import { Injectable } from '@angular/core';
import { TranslocoService } from '@ngneat/transloco';
import { EMPTY, Observable, catchError, filter, first, map, of, shareReplay, switchMap } from 'rxjs';
import { HandledError } from '../helpers/handled-error';
import { AdminDashboardData } from '../models/admin-dashboard-data';
import { AuthToken } from '../models/auth-token';
import { Cookbook, CookbookUser, CookbookWithCounts, CookbookWithUserMeta, SimpleCookbook } from '../models/cookbook';
import { FilterOption } from '../models/filter-option';
import { BaseIngredient, EditIngredientData, Ingredient, SimpleIngredient } from '../models/ingredient';
import { LoadingError } from '../models/loading-error';
import { PaginationMeta } from '../models/pagination-meta';
import { PaginationOptions } from '../models/pagination-options';
import { CreateRecipeData, DetailedRecipe, EditRecipeData, ListRecipe } from '../models/recipe';
import { RecipeImage } from '../models/recipe-image';
import { SortOption } from '../models/sort-option';
import { CreateUserData, DetailedUser, EditUserData, User } from '../models/user';
import { ConfigService } from '../services/config.service';
import { TOKEN_TYPE_HTTP_CONTEXT } from './auth.interceptor';

export enum TokenType {
  Access,
  AccessOptional,
  Refresh,
  None,
}

export interface SignedRoute {
  signature: string;
  expires: string;
}

interface ListParamOptions {
  pagination?: PaginationOptions;
  sort?: SortOption[];
  search?: string;
  filters?: FilterOption[];
}

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  static readonly API_VERSION = 1;

  constructor(private http: HttpClient, private config: ConfigService, private transloco: TranslocoService) {}

  public get url() {
    return `${this.config.get('apiUrl')}/v${ApiService.API_VERSION}`;
  }

  private get baseHttpHeaders() {
    let headers = new HttpHeaders({
      'X-Language': this.transloco.getActiveLang(),
    });

    return headers;
  }

  private get httpHeaders() {
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
      'X-Language': this.transloco.getActiveLang(),
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
      filter((response) => response instanceof HttpResponse),
      first()
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
      page: pagination.page.toString(),
      per_page: pagination.perPage?.toString(),
    };

    return ApiService.generateParams(paginationParams);
  }

  private static generateSortParams(sort?: SortOption[]) {
    if (!sort || sort.length === 0) {
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

  private static generateFilterParams(filters?: FilterOption[]) {
    if (!filters || filters.length === 0) {
      return null;
    }

    return filters.reduce((params, filterOption) => {
      let filter = `filter[${filterOption.column}]`;

      if (filterOption.type) {
        filter += `[${filterOption.type}]`;
      }

      let value = filterOption.value;

      if (value === null) {
        value = '\0';
      }

      return params.append(filter, value.toString());
    }, new HttpParams());
  }

  private static generateListParamOptions(options: ListParamOptions) {
    const baseParams = ApiService.generateParams({ search: options.search });
    const paginationParams = ApiService.generatePaginationParams(options.pagination);
    const sortParams = ApiService.generateSortParams(options.sort);
    const filterParams = ApiService.generateFilterParams(options.filters);

    return this.mergeParams(baseParams, paginationParams, sortParams, filterParams);
  }

  public getErrorMessage(error: unknown) {
    if (error instanceof HttpErrorResponse) {
      if (error.status !== 0 && error.error) {
        if (error.status === HttpStatusCode.UnprocessableEntity) {
          return error.error.errors[Object.keys(error.error.errors)[0]][0];
        } else {
          return error.error.message;
        }
      } else if (error.status === 504 || error.status === 0) {
        // gateway timeout -> offline

        return this.transloco.translate('messages.errors.noServerConnection');
      }

      return this.transloco.translate('messages.errors.unexpectedError');
    }

    return error;
  }

  public handleRequestError(request: Observable<unknown>, errorCallback?: (error: unknown) => void) {
    return request.pipe(
      switchMap(() => EMPTY),
      catchError((error) => {
        if (error instanceof HandledError) {
          error = error.error;
        }

        if (errorCallback) {
          errorCallback(error);
        }

        if (error instanceof HttpErrorResponse) {
          const apiErrorMessage = this.getErrorMessage(error);

          const message =
            typeof apiErrorMessage === 'string'
              ? apiErrorMessage
              : this.transloco.translate('messages.errors.errorOccurred');

          return of({
            type: 'HTTP_ERROR',
            error,
            httpError: {
              status: error.status,
              data: error.error,
              message,
            },
          } as LoadingError);
        }

        return of({
          type: 'UNKNOWN_ERROR',
          error,
        } as LoadingError);
      })
    );
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
        this.post<{ data: { user: DetailedUser; access_token: string; refresh_token: string | null } }>(
          '/auth/login',
          { email, password },
          TokenType.None
        ),

      signUp: (data: {
        name: string;
        email: string;
        password: string;
        language_code?: string;
        hcaptcha_token?: string;
      }) => {
        return this.post<{ data: { user: DetailedUser; access_token: string; refresh_token: string | null } }>(
          '/auth/sign-up',
          data,
          TokenType.None
        );
      },

      logout: () => this.post<void>('/auth/logout', {}, TokenType.Access),

      refreshToken: () =>
        this.post<{ data: { access_token: string; refresh_token: string } }>('/auth/refresh', {}, TokenType.Refresh),

      verifyEmail: (id: string, hash: string, signature: SignedRoute) =>
        this.post<void>(
          `/auth/email-verification/verify/${id}/${hash}`,
          {},
          TokenType.Access,
          ApiService.generateSignedRouteParams(signature)
        ),

      resendVerificationEmail: () => this.post<void>('/auth/email-verification/resend', {}, TokenType.Access),

      resetPassword: (token: string, email: string, password: string) =>
        this.post<void>('/auth/reset-password', { token, email, password }, TokenType.None),

      sendResetPasswordEmail: (email: string) =>
        this.post<void>('/auth/reset-password/send', { email }, TokenType.None),

      tokens: {
        getList: (pagination?: PaginationOptions) =>
          this.get<{ data: AuthToken[]; meta: PaginationMeta }>(
            `/auth/tokens`,
            TokenType.Access,
            ApiService.generateListParamOptions({ pagination })
          ),

        get: (id: number) => this.get<{ data: AuthToken }>(`/auth/tokens/${id}`, TokenType.Access),

        delete: (id: number) => this.delete<void>(`/auth/tokens/${id}`, TokenType.Access),

        deleteAll: () => this.delete<void>('/auth/tokens', TokenType.Access),

        groups: {
          getList: (groupId: number, pagination?: PaginationOptions) =>
            this.get<{ data: AuthToken[]; meta: PaginationMeta }>(
              `/auth/tokens/groups/${groupId}`,
              TokenType.Access,
              ApiService.generateListParamOptions({ pagination })
            ),
        },
      },
    };
  }

  public get users() {
    return {
      getList: (options: ListParamOptions) =>
        this.get<{ data: DetailedUser[]; meta: PaginationMeta }>(
          '/users',
          TokenType.Access,
          ApiService.generateListParamOptions(options)
        ),

      get: (id: number) => this.get<{ data: DetailedUser }>(`/users/${id}`, TokenType.Access),

      getByEmail: (email: string) => this.get<{ data: User }>(`/users/search/email/${email}`, TokenType.Access),

      create: (data: CreateUserData) => this.post<{ data: DetailedUser }>('/users', data, TokenType.Access),

      update: (id: number, data: EditUserData) =>
        this.put<{ data: DetailedUser }>(`/users/${id}`, data, TokenType.Access),

      delete: (id: number) => this.delete<void>(`/users/${id}`, TokenType.Access),
    };
  }

  public get categories() {
    return {
      getList: (all = false, sortByAmount?: 'asc' | 'desc') => {
        const sortParams = sortByAmount
          ? ApiService.generateSortParams([{ column: 'amount', dir: sortByAmount }])
          : null;

        return this.get<{ data: string[] }>(
          '/categories',
          TokenType.AccessOptional,
          ApiService.mergeParams(ApiService.generateParams({ all: all ? '' : undefined }), sortParams)
        );
      },
    };
  }

  public get recipes() {
    return {
      getList: (
        options: {
          all?: boolean;
        } & ListParamOptions
      ) => {
        const baseParams = ApiService.generateParams({ all: options.all ? '' : undefined });

        return this.get<{ data: ListRecipe[]; meta: PaginationMeta }>(
          '/recipes',
          TokenType.AccessOptional,
          ApiService.mergeParams(baseParams, ApiService.generateListParamOptions(options)!)
        );
      },

      get: (id: number) => this.get<{ data: DetailedRecipe }>(`/recipes/${id}`, TokenType.AccessOptional),
      getShared: (shareUuid: string) =>
        this.get<{ data: DetailedRecipe }>(`/recipes/shared/${shareUuid}`, TokenType.AccessOptional),

      create: (data: CreateRecipeData) => this.post<{ data: DetailedRecipe }>('/recipes', data, TokenType.Access),

      update: (id: number, data: EditRecipeData) =>
        this.put<{ data: DetailedRecipe }>(`/recipes/${id}`, data, TokenType.Access),

      delete: (id: number) => this.delete<void>(`/recipes/${id}`, TokenType.Access),

      trash: {
        getList: (options: ListParamOptions) =>
          this.get<{ data: ListRecipe[]; meta: PaginationMeta }>(
            '/recipe-trash',
            TokenType.Access,
            ApiService.generateListParamOptions(options)
          ),

        restoreRecipe: (recipeId: number) => this.put<void>(`/recipe-trash/${recipeId}`, {}, TokenType.Access),

        deleteRecipe: (recipeId: number) => this.delete<void>(`/recipe-trash/${recipeId}`, TokenType.Access),

        deleteAll: () => this.delete<void>('/recipe-trash', TokenType.Access),
      },

      images: {
        getList: (recipeId: number) =>
          this.get<{ data: RecipeImage[] }>(`/recipes/${recipeId}/images`, TokenType.AccessOptional),

        create: (recipeId: number, image: File) => {
          const fd = new FormData();
          fd.append('image', image, image.name);

          return this.http
            .post<{ data: RecipeImage }>(`${this.url}/recipes/${recipeId}/images`, fd, {
              headers: this.baseHttpHeaders,
              reportProgress: true,
              observe: 'events',
              context: new HttpContext().set(TOKEN_TYPE_HTTP_CONTEXT, TokenType.Access),
            })
            .pipe(
              map((event) => {
                if (event instanceof HttpResponse) {
                  return event;
                } else if (event.type === HttpEventType.UploadProgress) {
                  return Math.round((event.loaded / event.total!) * 100);
                }
                return null;
              }),
              shareReplay(1)
            );
        },

        delete: (imageId: number) => this.delete<void>(`/recipe-images/${imageId}`, TokenType.Access),
      },
    };
  }

  public get ingredients() {
    return {
      getList: (search?: string, sort?: SortOption[]) =>
        this.get<{ data: SimpleIngredient[] }>(
          '/ingredients',
          TokenType.Access,
          ApiService.generateListParamOptions({ search, sort })
        ),

      create: (recipeId: number, data: BaseIngredient) =>
        this.post<{ data: Ingredient }>(`/recipes/${recipeId}/ingredients`, data, TokenType.Access),

      update: (id: number, data: EditIngredientData) =>
        this.put<{ data: Ingredient }>(`/ingredients/${id}`, data, TokenType.Access),

      delete: (id: number) => this.delete<void>(`/ingredients/${id}`, TokenType.Access),
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

      getEditableList: () => this.get<{ data: SimpleCookbook[] }>(`/cookbooks/editable`, TokenType.Access),

      get: (id: number) => this.get<{ data: CookbookWithUserMeta }>(`/cookbooks/${id}`, TokenType.Access),

      getRecipes: (cookbookId: number, options: ListParamOptions) =>
        this.get<{ data: ListRecipe[]; meta: PaginationMeta }>(
          `/cookbooks/${cookbookId}/recipes`,
          TokenType.Access,
          ApiService.generateListParamOptions(options)
        ),

      getCategories: (cookbookId: number, sortByAmount?: 'asc' | 'desc') => {
        const sortParams = sortByAmount
          ? ApiService.generateSortParams([{ column: 'amount', dir: sortByAmount }])!
          : undefined;

        return this.get<{ data: string[] }>(`/cookbooks/${cookbookId}/categories`, TokenType.Access, sortParams);
      },

      create: (name: string) => this.post<{ data: Cookbook }>('/cookbooks', { name }, TokenType.Access),

      update: (id: number, name: string) =>
        this.put<{ data: Cookbook }>(`/cookbooks/${id}`, { name }, TokenType.Access),

      delete: (id: number) => this.delete<void>(`/cookbooks/${id}`, TokenType.Access),

      users: {
        getList: (cookbookId: number, options: ListParamOptions) =>
          this.get<{ data: CookbookUser[]; meta: PaginationMeta }>(
            `/cookbooks/${cookbookId}/users`,
            TokenType.Access,
            ApiService.generateListParamOptions(options)
          ),

        create: (cookbookId: number, userId: number, isAdmin: boolean) =>
          this.post<{ data: CookbookUser }>(
            `/cookbooks/${cookbookId}/users`,
            { user_id: userId, is_admin: isAdmin },
            TokenType.Access
          ),

        update: (cookbookId: number, userId: number, isAdmin: boolean) =>
          this.put<{ data: CookbookUser }>(
            `/cookbooks/${cookbookId}/users/${userId}`,
            { is_admin: isAdmin },
            TokenType.Access
          ),

        delete: (cookbookId: number, userId: number) =>
          this.delete<void>(`/cookbooks/${cookbookId}/users/${userId}`, TokenType.Access),
      },
    };
  }

  public get admin() {
    return {
      dashboard: {
        get: () => this.get<{ data: AdminDashboardData }>(`/admin/dashboard`, TokenType.Access),
      },
    };
  }
}
