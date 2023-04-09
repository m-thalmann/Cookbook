import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { ActivatedRoute, Router } from '@angular/router';
import { EMPTY, combineLatest, switchMap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { RecipeDetailComponent } from './components/recipe-detail/recipe-detail.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-recipe-detail-page',
  templateUrl: './recipe-detail-page.component.html',
  styleUrls: ['./recipe-detail-page.component.scss'],
  standalone: true,
  imports: [CommonModule, MatIconModule, RecipeDetailComponent],
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
    })
  );

  error$ = ApiService.handleRequestError(this.recipe$, (error) =>
    Logger.error('Error while loading recipe:', ApiService.getErrorMessage(error), error)
  );

  constructor(
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private router: Router,
    private auth: AuthService
  ) {}
}
