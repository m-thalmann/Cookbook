import { Component, EventEmitter, Input, OnInit, Output } from '@angular/core';
import { Pagination, Recipe } from 'src/app/core/api/ApiInterfaces';
import { ApiResponse } from 'src/app/core/api/ApiResponse';

@Component({
  selector: 'cb-recipe-list',
  templateUrl: './recipe-list.component.html',
  styleUrls: ['./recipe-list.component.scss'],
})
export class RecipeListComponent implements OnInit {
  @Input() reloadFunction!: (page: number) => Promise<ApiResponse<Pagination<Recipe>>>;
  @Input()
  set doReload(doReloadFunction: EventEmitter<void> | null) {
    doReloadFunction?.subscribe(() => this.reload());
  }

  @Output() loadingChange = new EventEmitter<boolean>();
  @Output() errorChange = new EventEmitter<boolean>();

  loading = false;
  error = false;
  private _page = 0;

  recipes: Pagination<Recipe> | null = null;

  constructor() {}

  ngOnInit() {
    this.reload();
  }

  get page() {
    return this._page;
  }

  set page(page: number) {
    this._page = page;
    this.reload();
  }

  async reload() {
    this.loading = true;
    this.loadingChange.emit(this.loading);

    this.error = false;
    this.errorChange.emit(this.loading);

    let recipes = await this.reloadFunction(this.page);

    if (recipes.isOK()) {
      this.recipes = recipes.value;
    } else {
      this.error = true;
      this.errorChange.emit(this.loading);

      console.error('Error loading recipes:', recipes.error);
    }

    this.loading = false;
    this.loadingChange.emit(this.loading);
  }
}
