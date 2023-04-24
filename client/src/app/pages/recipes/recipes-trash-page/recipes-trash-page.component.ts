import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, lastValueFrom, map, merge, shareReplay, switchMap, switchScan, take, tap } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { ListRecipe } from 'src/app/core/models/recipe';
import { PLACEHOLDER_RECIPE_IMAGE_URL } from 'src/app/core/models/recipe-image';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'app-recipes-trash-page',
  templateUrl: './recipes-trash-page.component.html',
  styleUrls: ['./recipes-trash-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    ErrorDisplayComponent,
  ],
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

    handledErrorInterceptor(),
    shareReplay(1)
  );

  loading$ = new BehaviorSubject<boolean>(false);

  actionLoading$ = new BehaviorSubject<boolean>(false);

  error$ = ApiService.handleRequestError(this.trashedRecipes$);

  constructor(
    private api: ApiService,
    private auth: AuthService,
    private snackbar: SnackbarService,
    private dialog: MatDialog,
    private transloco: TranslocoService
  ) {}

  getThumbnailUrl(recipe: ListRecipe) {
    if (!recipe.thumbnail) {
      return PLACEHOLDER_RECIPE_IMAGE_URL;
    }

    return recipe.thumbnail.url;
  }

  nextPage() {
    this.paginationOptions$.next({ ...this.paginationOptions$.value, page: this.paginationOptions$.value.page + 1 });
  }

  async clearTrash() {
    const confirmed = await lastValueFrom(
      this.dialog
        .open(ConfirmDialogComponent, {
          data: {
            title: this.transloco.translate('messages.areYouSure'),
            content: this.transloco.translate('messages.thisActionCantBeUndone'),
            btnConfirm: this.transloco.translate('actions.confirm'),
            btnDecline: this.transloco.translate('actions.abort'),
            warn: true,
          },
        })
        .afterClosed()
        .pipe(take(1))
    );

    if (!confirmed) {
      return;
    }

    this.actionLoading$.next(true);

    try {
      await lastValueFrom(this.api.recipes.trash.deleteAll());

      this.snackbar.info({ message: this.transloco.translate('messages.trashCleared') });

      this.resetList$.emit();
    } catch (e) {
      this.snackbar.warn({ message: this.transloco.translate('messages.errors.clearingTrash'), duration: null });
    } finally {
      this.actionLoading$.next(false);
    }
  }

  async restoreRecipe(recipe: ListRecipe) {
    this.actionLoading$.next(true);

    try {
      await lastValueFrom(this.api.recipes.trash.restoreRecipe(recipe.id));

      this.snackbar.info({ message: this.transloco.translate('messages.recipeRestored') });

      this.resetList$.emit();
    } catch (e) {
      this.snackbar.warn({ message: this.transloco.translate('messages.errors.restoringRecipe'), duration: null });
    } finally {
      this.actionLoading$.next(false);
    }
  }

  async permanentlyDeleteRecipe(recipe: ListRecipe) {
    const confirmed = await lastValueFrom(
      this.dialog
        .open(ConfirmDialogComponent, {
          data: {
            title: this.transloco.translate('messages.areYouSure'),
            content: this.transloco.translate('messages.thisActionCantBeUndone'),
            btnConfirm: this.transloco.translate('actions.delete'),
            btnDecline: this.transloco.translate('actions.abort'),
            warn: true,
          },
        })
        .afterClosed()
        .pipe(take(1))
    );

    if (!confirmed) {
      return;
    }

    this.actionLoading$.next(true);

    try {
      await lastValueFrom(this.api.recipes.trash.deleteRecipe(recipe.id));

      this.snackbar.info({ message: this.transloco.translate('messages.recipeDeleted') });

      this.resetList$.emit();
    } catch (e) {
      this.snackbar.warn({ message: this.transloco.translate('messages.errors.deletingRecipe'), duration: null });
    } finally {
      this.actionLoading$.next(false);
    }
  }
}
