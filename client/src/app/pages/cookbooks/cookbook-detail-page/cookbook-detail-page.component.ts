import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { Observable, combineLatest, map, shareReplay, switchMap } from 'rxjs';
import { CategoryChipListComponent } from 'src/app/components/category-chip-list/category-chip-list.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { PageSectionComponent } from 'src/app/components/page-section/page-section.component';
import { RecipeCardComponent } from 'src/app/components/recipe-card/recipe-card.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { PaginationMeta } from 'src/app/core/models/pagination-meta';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { CookbookHeaderComponent } from '../components/cookbook-header/cookbook-header.component';

@Component({
  selector: 'app-cookbook-detail-page',
  templateUrl: './cookbook-detail-page.component.html',
  styleUrls: ['./cookbook-detail-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    CookbookHeaderComponent,
    PageSectionComponent,
    CategoryChipListComponent,
    RecipeCardComponent,
    ErrorDisplayComponent,
    RepeatDirective,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbookDetailPageComponent {
  private reload$: Observable<number> = combineLatest([this.activatedRoute.params, this.auth.user$]).pipe(
    map(([params, _]) => parseInt(params['id']))
  );

  cookbook$ = this.reload$.pipe(
    switchMap((id) => this.api.cookbooks.get(id)),
    handledErrorInterceptor(),
    shareReplay(1)
  );
  categories$ = this.reload$.pipe(
    switchMap((id) => this.api.cookbooks.getCategories(id)),
    handledErrorInterceptor(),
    shareReplay(1)
  );
  recipes$ = this.reload$.pipe(
    switchMap((id) => this.api.cookbooks.getRecipes(id, { pagination: { perPage: 12, page: 1 } })),
    handledErrorInterceptor(),
    shareReplay(1)
  );

  cookbookError$ = ApiService.handleRequestError(this.cookbook$);
  categoriesError$ = ApiService.handleRequestError(this.categories$);
  recipesError$ = ApiService.handleRequestError(this.recipes$);

  constructor(private api: ApiService, private activatedRoute: ActivatedRoute, private auth: AuthService) {}

  getRemainingItems(meta?: PaginationMeta) {
    if (!meta) {
      return 0;
    }

    return meta.total - meta.count;
  }
}
