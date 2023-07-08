import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { EMPTY, combineLatest, shareReplay, switchMap, tap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SeoService } from 'src/app/core/services/seo.service';
import { RecipeDetailComponent } from './components/recipe-detail/recipe-detail.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-recipe-detail-page',
  templateUrl: './recipe-detail-page.component.html',
  styleUrls: ['./recipe-detail-page.component.scss'],
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, RecipeDetailComponent, ErrorDisplayComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailPageComponent {
  recipe$ = combineLatest([this.activatedRoute.params, this.auth.user$]).pipe(
    switchMap(([params, _]) => {
      if (params['id']) {
        return this.api.recipes.get(params['id']);
      }
      if (params['shareUuid']) {
        return this.api.recipes.getShared(params['shareUuid']);
      }

      Logger.error('RecipeDetailComponent: No id defined');

      this.router.navigate(['/']);

      return EMPTY;
    }),
    tap((recipeResponse) => {
      const recipe = recipeResponse.body?.data;

      if (recipe) {
        this.seo.setTitle(recipe.name);

        if (recipe.description) {
          this.seo.setDescription(recipe.description);
        }
      }
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.recipe$, (error) =>
    Logger.error('Error while loading recipe:', this.api.getErrorMessage(error), error)
  );

  constructor(
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private router: Router,
    private auth: AuthService,
    private seo: SeoService
  ) {}
}
