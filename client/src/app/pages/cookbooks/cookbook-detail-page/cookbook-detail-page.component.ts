import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { combineLatest, map, Observable, switchMap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { PaginationMeta } from 'src/app/core/models/pagination-meta';

@Component({
  selector: 'app-cookbook-detail-page',
  templateUrl: './cookbook-detail-page.component.html',
  styleUrls: ['./cookbook-detail-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookDetailPageComponent {
  private reload$: Observable<number> = combineLatest([this.activatedRoute.params, this.auth.user$]).pipe(
    map(([params, _]) => parseInt(params['id']))
  );

  cookbook$ = this.reload$.pipe(switchMap((id) => this.api.cookbooks.get(id)));
  categories$ = this.reload$.pipe(switchMap((id) => this.api.cookbooks.getCategories(id)));
  recipes$ = this.reload$.pipe(switchMap((id) => this.api.cookbooks.getRecipes(id, {})));
  // TODO: error handling

  constructor(private api: ApiService, private activatedRoute: ActivatedRoute, private auth: AuthService) {}

  getRemainingItems(meta?: PaginationMeta) {
    if (!meta) {
      return 0;
    }

    return meta.total - meta.count;
  }
}
