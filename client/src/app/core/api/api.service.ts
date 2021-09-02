import { HttpClient, HttpErrorResponse, HttpEventType, HttpHeaders, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Observable, of } from 'rxjs';
import { catchError, map } from 'rxjs/operators';
import { UserService } from '../auth/user.service';
import { ConfigService } from '../config/config.service';
import {
  EditIngredient,
  EditRecipe,
  Ingredient,
  ListIngredient,
  NewIngredient,
  NewRecipe,
  ApiOptions,
  Pagination,
  Recipe,
  RecipeFull,
  RecipeImage,
  User,
  UserFull,
  ServerInformation,
  ServerConfig,
  CategoryInformation,
} from './ApiInterfaces';
import { ApiResponse } from './ApiResponse';

@Injectable({
  providedIn: 'root',
})
export class ApiService {
  constructor(
    private http: HttpClient,
    private config: ConfigService,
    private user: UserService,
    private snackBar: MatSnackBar
  ) {}

  /**
   * The http-options containing the token, if a user is logged-in
   */
  private get httpOptions(): { observe: 'response'; headers: HttpHeaders } {
    let headers = new HttpHeaders({
      'Content-Type': 'application/json',
    });

    let token = this.user.token;

    if (this.user.isLoggedin && token) {
      headers = headers.append('Authorization', token);
    }

    return {
      observe: 'response',
      headers: headers,
    };
  }

  private get httpOptionsImageUpload(): { observe: 'events'; headers: HttpHeaders; reportProgress: boolean } {
    let headers = new HttpHeaders();

    let token = this.user.token;

    if (this.user.isLoggedin && token) {
      headers = headers.append('Authorization', token);
    }

    return {
      observe: 'events',
      headers: headers,
      reportProgress: true,
    };
  }

  private get queryToken() {
    if (this.user.isLoggedin && this.user.token) {
      return `token=${encodeURIComponent(this.user.token)}`;
    } else {
      return null;
    }
  }

  /**
   * The api url from the config
   */
  get URL() {
    return this.config.get('api_url', 'http://localhost:80');
  }

  /**
   * Handles the received http-response-observable and returns the appropriate ApiResponse instance
   *
   * @param observable the http-response-observable to handle
   * @returns the created ApiResponse instance
   */
  private async handleResponse<T>(observable: Observable<HttpResponse<T | null>>): Promise<ApiResponse<T>> {
    let prom = observable.toPromise();

    let status: number;
    let value: T | null;

    try {
      let ret = await prom;

      status = ret.status;
      value = ret.body;

      if (ret.headers.has('X-Logout')) {
        this.user.logout('unauthorized', null);
      }
    } catch (e) {
      return this.handleError(e);
    }

    return new ApiResponse<T>(status, value);
  }

  private handleError<T>(e: HttpErrorResponse) {
    let status = e.status;
    let error = e.error;

    if (status != 404) {
      if (status == 401 && this.user.isLoggedin) {
        this.user.logout('unauthorized');
      } else if (status == 403) {
        if (this.user.isLoggedin) {
          this.snackBar.open('You are not authorized to perform this action!', 'OK', {
            duration: 10000,
            panelClass: 'action-warn',
          });
        }
      }

      return new ApiResponse<T>(e.status, null, error);
    }

    return new ApiResponse<T>(status, error);
  }

  private static getOptionsQuery(options: ApiOptions) {
    let query = [];

    if (typeof options.page !== 'undefined') {
      query.push(`page=${options.page}`);
    }
    if (typeof options.itemsPerPage !== 'undefined' && options.itemsPerPage !== null) {
      query.push(`itemsPerPage=${options.itemsPerPage}`);
    }
    if (typeof options.sort !== 'undefined') {
      query.push(`sort=${options.sort}`);
    }
    if (typeof options.sortDirection !== 'undefined' && options.sortDirection !== null) {
      query.push(`sortDir=${options.sortDirection}`);
    }

    return query;
  }

  private static buildQueryString(query: string[]) {
    if (query.length > 0) {
      return `?${query.join('&')}`;
    }

    return '';
  }

  private static buildOptionsQueryString(options: ApiOptions) {
    return ApiService.buildQueryString(ApiService.getOptionsQuery(options));
  }

  private get<T>(url: string, httpOptions = this.httpOptions) {
    return this.handleResponse<T>(this.http.get<T>(url, httpOptions));
  }
  private post<T>(url: string, data: any = {}, httpOptions = this.httpOptions) {
    return this.handleResponse<T>(this.http.post<T>(url, data, httpOptions));
  }
  private put<T>(url: string, data: any, httpOptions = this.httpOptions) {
    return this.handleResponse<T>(this.http.put<T>(url, data, httpOptions));
  }
  private delete<T>(url: string, httpOptions = this.httpOptions) {
    return this.handleResponse<T>(this.http.delete<T>(url, httpOptions));
  }

  // User

  loginUser(email: string, password: string) {
    return this.post<{ user: User; token: string; info: string }>(`${this.URL}/auth/login`, {
      email: email,
      password: password,
    });
  }

  registerUser(email: string, password: string, name: string, hcaptchaToken: string | null) {
    return this.post<{ user: User; info: string }>(`${this.URL}/auth/register`, {
      email: email,
      password: password,
      name: name,
      hcaptchaToken: hcaptchaToken ? hcaptchaToken : undefined,
    });
  }

  updateUser(values: { oldPassword?: string; email?: string; name?: string; password?: string }) {
    return this.put<User>(`${this.URL}/auth`, values);
  }

  deleteUser() {
    return this.delete<any>(`${this.URL}/auth`);
  }

  checkAuthentication() {
    return this.get<{ user: User; info: string }>(`${this.URL}/auth`);
  }

  verifyEmail(email: string, code: string) {
    return this.post<any>(`${this.URL}/auth/verifyEmail`, {
      email: email,
      code: code,
    });
  }

  resendVerificationEmail(email: string) {
    return this.post<any>(`${this.URL}/auth/verifyEmail/resend`, {
      email: email,
    });
  }

  registrationEnabled() {
    return this.get<boolean>(`${this.URL}/auth/registrationEnabled`);
  }

  resetPassword(email: string, token: string, password: string) {
    return this.post<any>(`${this.URL}/auth/resetPassword`, { email: email, token: token, password: password });
  }

  sendResetPasswordEmail(email: string) {
    return this.post<any>(`${this.URL}/auth/resetPassword/send`, { email: email });
  }

  // Recipe

  getRecipes(options: ApiOptions) {
    return this.get<Pagination<Recipe>>(`${this.URL}/recipes${ApiService.buildOptionsQueryString(options)}`);
  }

  searchRecipes(search: string, options: ApiOptions) {
    return this.get<Pagination<Recipe>>(
      `${this.URL}/recipes/search/${search}${ApiService.buildOptionsQueryString(options)}`
    );
  }

  getRecipesForCategory(category: string, options: ApiOptions) {
    return this.get<Pagination<Recipe>>(
      `${this.URL}/recipes/category/${category}${ApiService.buildOptionsQueryString(options)}`
    );
  }

  getRecipesForUser(id: number, options: ApiOptions) {
    return this.get<Pagination<Recipe>>(
      `${this.URL}/users/id/${id}/recipes${ApiService.buildOptionsQueryString(options)}`
    );
  }

  getRecipeById(id: number) {
    return this.get<RecipeFull>(`${this.URL}/recipes/id/${id}`);
  }

  createRecipe(recipe: NewRecipe) {
    return this.post<RecipeFull>(`${this.URL}/recipes`, recipe);
  }

  editRecipe(id: number, recipe: EditRecipe) {
    return this.put<RecipeFull>(`${this.URL}/recipes/id/${id}`, recipe);
  }

  deleteRecipe(id: number) {
    return this.delete<any>(`${this.URL}/recipes/id/${id}`);
  }

  // Ingredient

  addIngredient(recipeId: number, ingredient: NewIngredient) {
    return this.post<Ingredient>(`${this.URL}/recipes/id/${recipeId}/ingredients`, ingredient);
  }

  editIngredient(id: number, ingredient: EditIngredient) {
    return this.put<Ingredient>(`${this.URL}/ingredients/id/${id}`, ingredient);
  }

  deleteIngredient(id: number) {
    return this.delete<any>(`${this.URL}/ingredients/id/${id}`);
  }

  getIngredientsList() {
    return this.get<ListIngredient[]>(`${this.URL}/ingredients/list`);
  }

  // Recipe Images

  getRecipeImages(recipeId: number) {
    return this.get<RecipeImage[]>(`${this.URL}/recipes/id/${recipeId}/images`);
  }

  getRecipeImagesCount(recipeId: number) {
    return this.get<number>(`${this.URL}/recipes/id/${recipeId}/images/count`);
  }

  getRecipeImageURL(recipeId: number, number: number, maxSize: number | null = null, includeToken = true) {
    let query = [];

    if (includeToken && this.queryToken) {
      query.push(this.queryToken);
    }
    if (maxSize) {
      query.push(`maxSize=${maxSize}`);
    }

    let queryString = '';

    if (query.length > 0) {
      queryString = `?${query.join('&')}`;
    }

    return `${this.URL}/recipes/id/${recipeId}/images/number/${number}${queryString}`;
  }

  getRecipeImageURLById(id: number, maxSize: number | null = null, includeToken = true) {
    let query = [];

    if (includeToken && this.queryToken) {
      query.push(this.queryToken);
    }
    if (maxSize) {
      query.push(`maxSize=${maxSize}`);
    }

    let queryString = '';

    if (query.length > 0) {
      queryString = `?${query.join('&')}`;
    }

    return `${this.URL}/recipeImages/id/${id}${queryString}`;
  }

  addRecipeImage(recipeId: number, image: File) {
    const fd = new FormData();
    fd.append('image', image, image.name);

    return this.http
      .post<RecipeImage>(`${this.URL}/recipes/id/${recipeId}/images`, fd, this.httpOptionsImageUpload)
      .pipe(
        catchError((error: HttpErrorResponse) => {
          return of(this.handleError<RecipeImage>(error));
        }),
        map((event) => {
          if (event instanceof ApiResponse) {
            return event;
          } else {
            switch (event.type) {
              case HttpEventType.UploadProgress:
                return Math.round((event.loaded / event.total!) * 100);
              case HttpEventType.Response:
                return new ApiResponse<RecipeImage>(event.status, event.body);
            }
          }
          return null;
        })
      );
  }

  deleteRecipeImage(id: number) {
    return this.delete<any>(`${this.URL}/recipeImages/id/${id}`);
  }

  // Categories

  getCategories() {
    return this.get<CategoryInformation[]>(`${this.URL}/categories`);
  }

  // Admin

  get admin() {
    return {
      getUsers: (search: string | null, options: ApiOptions) => {
        let query = ApiService.getOptionsQuery(options);

        if (search) {
          query.push(`search=${encodeURIComponent(search)}`);
        }

        return this.get<Pagination<UserFull>>(`${this.URL}/admin/users${ApiService.buildQueryString(query)}`);
      },
      getUser: (id: number) => {
        return this.get<UserFull>(`${this.URL}/admin/users/id/${id}`);
      },
      createUser: (email: string, password: string, name: string, isAdmin = false, verifyEmail = true) => {
        return this.post<UserFull>(`${this.URL}/admin/users`, {
          email: email,
          password: password,
          name: name,
          isAdmin: isAdmin,
          verifyEmail: verifyEmail,
        });
      },
      updateUser: (
        userId: number,
        values: { email?: string; name?: string; password?: string; isAdmin?: boolean; emailVerified?: boolean }
      ) => {
        return this.put<UserFull>(`${this.URL}/admin/users/id/${userId}`, values);
      },
      deleteUser: (userId: number) => {
        return this.delete<any>(`${this.URL}/admin/users/id/${userId}`);
      },
      resetUserPassword: (userId: number) => {
        return this.post<{ user: UserFull; password: string }>(`${this.URL}/admin/users/id/${userId}/resetPassword`);
      },
      getRecipes: (search: string | null, filterUserId: number | null, options: ApiOptions) => {
        let query = ApiService.getOptionsQuery(options);

        if (search) {
          query.push(`search=${encodeURIComponent(search)}`);
        }
        if (filterUserId) {
          query.push(`filterUserId=${filterUserId}`);
        }

        return this.get<Pagination<Recipe>>(`${this.URL}/admin/recipes${ApiService.buildQueryString(query)}`);
      },
      getServerInformation: () => {
        return this.get<ServerInformation>(`${this.URL}/admin/information`);
      },
      getServerConfig: () => {
        return this.get<ServerConfig>(`${this.URL}/admin/server/config`);
      },
    };
  }
}
