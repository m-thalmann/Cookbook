import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { combineLatest, switchMap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';

@Component({
  selector: 'app-recipe-detail-page',
  templateUrl: './recipe-detail-page.component.html',
  styleUrls: ['./recipe-detail-page.component.scss'],
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

      throw new Error('RecipeDetailComponent: No id defined');
    })
  );
  // TODO: error handling

  constructor(private api: ApiService, private activatedRoute: ActivatedRoute, private auth: AuthService) {}
}
