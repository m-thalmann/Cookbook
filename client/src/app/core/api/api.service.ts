import { HttpClient, HttpHeaders, HttpResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { Observable } from 'rxjs';
import { UserService } from '../auth/user.service';
import { ConfigService } from '../config/config.service';
import {
  EditIngredient,
  EditRecipe,
  Ingredient,
  NewIngredient,
  NewRecipe,
  Options,
  Pagination,
  Recipe,
  RecipeFull,
  RecipeImage,
  SearchIngredient,
  User,
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

  private get httpOptionsImageUpload(): { observe: 'response'; headers: HttpHeaders } {
    let headers = new HttpHeaders();

    let token = this.user.token;

    if (this.user.isLoggedin && token) {
      headers = headers.append('Authorization', token);
    }

    return {
      observe: 'response',
      headers: headers,
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
      status = e.status;
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
      value = error;
    }

    return new ApiResponse<T>(status, value);
  }

  private getQueryString(options: Options) {
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

    if (query.length > 0) {
      return `?${query.join('&')}`;
    }

    return '';
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

  // Recipe

  getRecipes(options: Options) {
    return this.get<Pagination<Recipe>>(`${this.URL}/recipes${this.getQueryString(options)}`);
  }

  searchRecipes(search: string, options: Options) {
    return this.get<Pagination<Recipe>>(`${this.URL}/recipes/search/${search}${this.getQueryString(options)}`);
  }

  getRecipesForCategory(category: string, options: Options) {
    return this.get<Pagination<Recipe>>(`${this.URL}/recipes/category/${category}${this.getQueryString(options)}`);
  }

  getRecipesForUser(id: number, options: Options) {
    return this.get<Pagination<Recipe>>(`${this.URL}/users/id/${id}/recipes${this.getQueryString(options)}`);
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

  searchIngredient(search: string) {
    if (search.length < 3) {
      return null;
    }

    return this.get<SearchIngredient[]>(`${this.URL}/ingredients/search/${search}`);
  }

  // Recipe Images

  getRecipeImages(recipeId: number) {
    return this.get<RecipeImage[]>(`${this.URL}/recipes/id/${recipeId}/images`);
  }

  getRecipeImagesCount(recipeId: number) {
    return this.get<number>(`${this.URL}/recipes/id/${recipeId}/images/count`);
  }

  getRecipeImageURL(recipeId: number, number: number, thumbnailWidth: number | null = null) {
    let query = [];

    if (this.queryToken) {
      query.push(this.queryToken);
    }
    if (thumbnailWidth) {
      query.push(`thumbnailWidth=${thumbnailWidth}`);
    }

    let queryString = '';

    if (query.length > 0) {
      queryString = `?${query.join('&')}`;
    }

    return `${this.URL}/recipes/id/${recipeId}/images/number/${number}${queryString}`;
  }

  getRecipeImageURLById(id: number, thumbnailWidth: number | null = null) {
    let query = [];

    if (this.queryToken) {
      query.push(this.queryToken);
    }
    if (thumbnailWidth) {
      query.push(`thumbnailWidth=${thumbnailWidth}`);
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

    return this.post<RecipeImage>(`${this.URL}/recipes/id/${recipeId}/images`, fd, this.httpOptionsImageUpload);
  }

  deleteRecipeImage(id: number) {
    return this.delete<any>(`${this.URL}/recipeImages/id/${id}`);
  }

  // Categories

  getCategories() {
    return this.get<string[]>(`${this.URL}/categories`);
  }
}
