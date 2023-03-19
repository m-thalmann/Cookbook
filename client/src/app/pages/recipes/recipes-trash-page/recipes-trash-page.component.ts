import { HttpResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import {
  BehaviorSubject,
  lastValueFrom,
  merge,
  shareReplay,
  switchMap,
  tap,
  startWith,
  scan,
  switchScan,
  map,
} from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { ListRecipe } from 'src/app/core/models/recipe';
import { PLACEHOLDER_RECIPE_IMAGE_URL } from 'src/app/core/models/recipe-image';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'app-recipes-trash-page',
  templateUrl: './recipes-trash-page.component.html',
  styleUrls: ['./recipes-trash-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipesTrashPageComponent {
  private resetList$ = new EventEmitter<void>();

  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1 });

  trashedRecipes$ = merge(this.auth.user$, this.resetList$).pipe(
    switchMap(() => {
      this.paginationOptions$.next({ ...this.paginationOptions$.value, page: 1 });

      return this.paginationOptions$.pipe(
        tap(() => this.loading$.next(true)),
        switchScan(
          (acc, paginationOptions) => {
            return this.api.recipes.trash
              .getList({ pagination: paginationOptions, sort: [{ column: 'deleted_at', dir: 'asc' }] })
              .pipe(
                map((response) => ({
                  totalRecipes: response.body!.meta.total,
                  hasMoreItems: response.body!.meta.current_page < response.body!.meta.last_page,
                  recipes: [...acc.recipes, ...response.body!.data],
                }))
              );
          },
          { totalRecipes: 0, hasMoreItems: true, recipes: [] }
        ),
        tap(() => this.loading$.next(false))
      );
    }),

    shareReplay(1)
  );

  loading$ = new BehaviorSubject<boolean>(false);

  actionLoading$ = new BehaviorSubject<boolean>(false);

  constructor(private api: ApiService, private auth: AuthService, private snackbar: SnackbarService) {}

  getThumbnailUrl(recipe: ListRecipe) {
    if (!recipe.thumbnail) {
      return PLACEHOLDER_RECIPE_IMAGE_URL;
    }

    return recipe.thumbnail.url;
  }

  nextPage() {
    this.paginationOptions$.next({ ...this.paginationOptions$.value, page: this.paginationOptions$.value.page + 1 });
  }

  // TODO: add confirmation dialogs

  async clearTrash() {
    this.actionLoading$.next(true);

    try {
      await lastValueFrom(this.api.recipes.trash.deleteAll());

      this.snackbar.info({ message: 'All trashed recipes deleted permanently' });

      this.resetList$.emit();
    } catch (e) {
      this.snackbar.warn({ message: 'Error clearing trash', duration: null });
    } finally {
      this.actionLoading$.next(false);
    }
  }

  async restoreRecipe(recipe: ListRecipe) {
    this.actionLoading$.next(true);

    try {
      await lastValueFrom(this.api.recipes.trash.restoreRecipe(recipe.id));

      this.snackbar.info({ message: 'Recipe restored successfully' });

      this.resetList$.emit();
    } catch (e) {
      this.snackbar.warn({ message: 'Error restoring recipe', duration: null });
    } finally {
      this.actionLoading$.next(false);
    }
  }

  async permanentlyDeleteRecipe(recipe: ListRecipe) {
    this.actionLoading$.next(true);

    try {
      await lastValueFrom(this.api.recipes.trash.deleteRecipe(recipe.id));

      this.snackbar.info({ message: 'Recipe permanently deleted' });

      this.resetList$.emit();
    } catch (e) {
      this.snackbar.warn({ message: 'Error permanently deleting recipe', duration: null });
    } finally {
      this.actionLoading$.next(false);
    }
  }
}

