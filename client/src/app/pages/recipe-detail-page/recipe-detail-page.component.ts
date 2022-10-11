import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { switchMap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';

@Component({
  selector: 'app-recipe-detail-page',
  templateUrl: './recipe-detail-page.component.html',
  styleUrls: ['./recipe-detail-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class RecipeDetailPageComponent {
  recipe$ = this.activatedRoute.params.pipe(
    switchMap((params) => {
      return this.api.recipes.getRecipe(params['id']);
    })
  );

  constructor(private api: ApiService, private activatedRoute: ActivatedRoute) {}
}

