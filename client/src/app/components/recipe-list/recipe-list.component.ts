import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { Options, Pagination, Recipe, RecipeSortDirection } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';

@Component({
  selector: 'cb-recipe-list',
  templateUrl: './recipe-list.component.html',
  styleUrls: ['./recipe-list.component.scss'],
})
export class RecipeListComponent implements OnInit {
  @Input()
  set sort(sort: string) {
    this._sort = sort;
    this.sortDirection = RecipeSortDirection[sort];
  }
  @Input() sortDirection: 'asc' | 'desc' = 'desc';

  @Input() reloadFunction!: (options: Options) => Promise<ApiResponse<Pagination<Recipe>>> | null;

  @Output() loadingChange = new EventEmitter<boolean>();
  @Output() errorChange = new EventEmitter<boolean>();

  results: number | null = null;

  loading = false;
  error = false;
  private _page = 0;

  private _sort: string = 'publishDate';

  recipes: Pagination<Recipe> | null = null;

  constructor() {}

  ngOnInit() {
    this.reload();
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

        console.error('Error loading recipes:', recipes.error);
      }
    }

    this.loading = false;
    this.loadingChange.emit(this.loading);
  }
}
