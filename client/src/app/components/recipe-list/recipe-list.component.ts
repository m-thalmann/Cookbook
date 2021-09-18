import { Component, EventEmitter, Input, OnDestroy, OnInit, Output } from '@angular/core';
import { ApiOptions, Pagination, Recipe, RecipeSortDirection } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';
import { UserService } from 'src/app/core/auth/user.service';
import { Logger, LoggerColor, SubSink } from 'src/app/core/functions';

@Component({
  selector: 'cb-recipe-list',
  templateUrl: './recipe-list.component.html',
  styleUrls: ['./recipe-list.component.scss'],
})
export class RecipeListComponent implements OnInit, OnDestroy {
  private subSink = new SubSink();

  @Input()
  set sort(sort: string) {
    this._sort = sort;
    this.sortDirection = RecipeSortDirection[sort];
  }
  @Input() sortDirection: 'asc' | 'desc' = 'desc';
  @Input() display: 'list' | 'grid' = 'list';

  @Input() reloadFunction!: (options: ApiOptions) => Promise<ApiResponse<Pagination<Recipe>>> | null;

  @Input() compact = false;

  @Output() loadingChange = new EventEmitter<boolean>();
  @Output() errorChange = new EventEmitter<boolean>();

  results: number | null = null;

  loading = false;
  error = false;
  private _page = 0;

  private _sort: string = 'publishDate';

  recipes: Pagination<Recipe> | null = null;

  constructor(private user: UserService) {}

  ngOnInit() {
    this.reload();

    this.subSink.push(this.user.userChanged.subscribe(this.reload.bind(this)));
  }

  get sort() {
    return this._sort;
  }

  get page() {
    return this._page;
  }

  set page(page: number) {
    this._page = page;
    this.reload();
  }

  sortChange(sort: string) {
    this.sort = sort;
    this.sortDirection = RecipeSortDirection[sort];
    this.page = 0;
  }

  toggleSortDirection() {
    this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    this.page = 0;
  }

  toggleDisplay() {
    this.display = this.display === 'list' ? 'grid' : 'list';
  }

  async reload() {
    this.loading = true;
    this.loadingChange.emit(false);

    this.error = false;
    this.errorChange.emit(false);

    let recipes = await this.reloadFunction({
      page: this.page,
      sort: this.sort,
      sortDirection: this.sortDirection,
    });

    if (recipes !== null) {
      if (recipes.isOK()) {
        this.recipes = recipes.value;

        if (recipes.value) {
          this.results = recipes.value.total_items;
        }
      } else {
        this.error = true;
        this.errorChange.emit(true);

        Logger.error('RecipeList', LoggerColor.green, 'Error loading recipes:', recipes.error);
      }
    }

    this.loading = false;
    this.loadingChange.emit(this.loading);
  }

  /**
   * Returns an empty array with n elements
   *
   * @param n length of array
   * @returns An empty array with n elements
   */
  getArray(n: number) {
    return Array(n);
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
