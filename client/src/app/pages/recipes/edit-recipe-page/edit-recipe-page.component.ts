import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTabsModule } from '@angular/material/tabs';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, EMPTY, combineLatest, of, shareReplay, startWith, switchMap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { EditRecipeDetailsComponent } from './components/edit-recipe-details/edit-recipe-details.component';
import { EditRecipeImagesComponent } from './components/edit-recipe-images/edit-recipe-images.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-edit-recipe-page',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    TranslocoModule,
    MatIconModule,
    MatButtonModule,
    MatTabsModule,
    EditRecipeDetailsComponent,
    EditRecipeImagesComponent,
    ErrorDisplayComponent,
  ],
  templateUrl: './edit-recipe-page.component.html',
  styleUrls: ['./edit-recipe-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EditRecipePageComponent {
  private recipeId$ = combineLatest([this.activatedRoute.params, this.auth.user$]).pipe(
    switchMap(([params, _]) => {
      if (params['id']) {
        return of(params['id']);
      }

      Logger.error('EditRecipePage: No id defined');

      this.router.navigate(['/']);

      return EMPTY;
    })
  );

  updateRecipe$ = new EventEmitter<void>();

  recipe$ = combineLatest([this.recipeId$, this.updateRecipe$.pipe(startWith(undefined))]).pipe(
    switchMap(([recipeId, _]) => this.api.recipes.get(recipeId)),
    switchMap((recipe) => {
      if (recipe.body?.data.user_can_edit) {
        return of(recipe);
      }

      Logger.error('EditRecipePage: User cannot edit recipe');
      this.snackbar.warn('messages.errors.userCantEditRecipe', { translateMessage: true });

      this.router.navigate(['/recipes', recipe.body!.data.id]);

      return EMPTY;
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.recipe$, (error) =>
    Logger.error('Error while loading recipe:', this.api.getErrorMessage(error), error)
  );

  detailsSaving$ = new BehaviorSubject<boolean>(false);
  imagesSaving$ = new BehaviorSubject<boolean>(false);

  constructor(
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private router: Router,
    private auth: AuthService,
    private snackbar: SnackbarService
  ) {}

  onDetailsSaving(saving: boolean) {
    this.detailsSaving$.next(saving);
  }

  onImagesSaving(saving: boolean) {
    this.imagesSaving$.next(saving);
  }
}

