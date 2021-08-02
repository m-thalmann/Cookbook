import { HttpClient, HttpHeaders, HttpResponse } from '@angular/common/http';
import { Expression } from '@angular/compiler';
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
  Pagination,
  Recipe,
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
    } catch (e) {
      status = e.status;
      let error = e.error;

      if (status != 404) {
        if (status == 401 && this.user.isLoggedin) {
          this.user.logoutReason('unauthorized');
        } else if (status == 403) {
          if (this.user.isLoggedin) {
            this.snackBar.open('You are not authorized to perform this action!', 'OK', {
              duration: 10000,
              panelClass: 'action-warn',
            });
          }

          error = null;
        }

        return new ApiResponse<T>(e.status, null, error);
      }
      value = error;
    }

    return new ApiResponse<T>(status, value);
  }

  // User

  loginUser(email: string, password: string) {
    return this.handleResponse<{ user: User; token: string; info: string }>(
      this.http.post<{ user: User; token: string; info: string }>(
        `${this.URL}/auth/login`,
        { email: email, password: password },
        this.httpOptions
      )
    );
  }

  registerUser(email: string, password: string, name: string) {
    return this.handleResponse<{ user: User; token: string; info: string }>(
      this.http.post<{ user: User; token: string; info: string }>(
        `${this.URL}/auth/register`,
        { email: email, password: password, name: name },
        this.httpOptions
      )
    );
  }

  // TODO: edit user
  // updateUser(
  //   id: number,
  //   values: {
  //     old_password?: string;
  //     username?: string;
  //     password?: string;
  //     role?: Role;
  //   }
  // ) {
  //   return this.handleResponse<{}>(this.http.put<{}>(`${this.URL}/users/${id}`, values, this.httpOptions));
  // }

  deleteUser() {
    return this.handleResponse<{}>(this.http.delete<{}>(`${this.URL}/auth`, this.httpOptions));
  }

  checkAuthentication() {
    return this.handleResponse<{ user: User; info: string }>(
      this.http.get<{ user: User; info: string }>(`${this.URL}/auth`, this.httpOptions)
    );
  }

  registrationEnabled() {
    return this.handleResponse<boolean>(
      this.http.get<boolean>(`${this.URL}/auth/registrationEnabled`, this.httpOptions)
    );
  }

  // Recipe

  getRecipes(page: number = 0, items_per_page: number | null = null) {
    let query = [`page=${page}`];

    if (items_per_page !== null) {
      query.push(`items_per_page=${items_per_page}`);
    }

    return this.handleResponse<Pagination<Recipe>>(
      this.http.get<Pagination<Recipe>>(`${this.URL}/recipes?${query.join('&')}`, this.httpOptions)
    );
  }

  searchRecipes(search: string, page: number = 0, items_per_page: number | null = null) {
    let query = [`page=${page}`];

    if (items_per_page !== null) {
      query.push(`items_per_page=${items_per_page}`);
    }

    return this.handleResponse<Pagination<Recipe>>(
      this.http.get<Pagination<Recipe>>(`${this.URL}/recipes/search/${search}?${query.join('&')}`, this.httpOptions)
    );
  }

  getRecipesForCategory(category: string, page: number = 0, items_per_page: number | null = null) {
    let query = [`page=${page}`];

    if (items_per_page !== null) {
      query.push(`items_per_page=${items_per_page}`);
    }

    return this.handleResponse<Pagination<Recipe>>(
      this.http.get<Pagination<Recipe>>(`${this.URL}/recipes/category/${category}?${query.join('&')}`, this.httpOptions)
    );
  }

  getRecipeById(id: number) {
    return this.handleResponse<Recipe>(this.http.get<Recipe>(`${this.URL}/recipes/id/${id}`, this.httpOptions));
  }

  createRecipe(recipe: NewRecipe) {
    return this.handleResponse<Recipe>(this.http.post<Recipe>(`${this.URL}/recipes`, recipe, this.httpOptions));
  }

  editRecipe(id: number, recipe: EditRecipe) {
    return this.handleResponse<Recipe>(this.http.put<Recipe>(`${this.URL}/recipes/id/${id}`, recipe, this.httpOptions));
  }

  deleteRecipe(id: number) {
    return this.handleResponse<any>(this.http.delete(`${this.URL}/recipes/id/${id}`, this.httpOptions));
  }

  // Ingredient

  addIngredient(recipeId: number, ingredient: NewIngredient) {
    return this.handleResponse<Ingredient>(
      this.http.post<Ingredient>(`${this.URL}/recipes/id/${recipeId}/ingredients`, ingredient, this.httpOptions)
    );
  }

  editIngredient(id: number, ingredient: EditIngredient) {
    return this.handleResponse<Ingredient>(
      this.http.put<Ingredient>(`${this.URL}/ingredients/id/${id}`, ingredient, this.httpOptions)
    );
  }

  deleteIngredient(id: number) {
    return this.handleResponse<any>(this.http.delete(`${this.URL}/ingredients/id/${id}`, this.httpOptions));
  }

  searchIngredient(search: string) {
    if (search.length < 3) {
      return null;
    }

    return this.handleResponse<SearchIngredient[]>(
      this.http.get<SearchIngredient[]>(`${this.URL}/ingredients/search/${search}`, this.httpOptions)
    );
  }
}
