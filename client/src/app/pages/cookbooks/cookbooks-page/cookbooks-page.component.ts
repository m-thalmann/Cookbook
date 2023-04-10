import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { BehaviorSubject, map, merge, shareReplay, switchMap, switchScan, tap } from 'rxjs';
import { CookbookCardComponent } from 'src/app/components/cookbook-card/cookbook-card.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { PaginationOptions } from 'src/app/core/models/pagination-options';

const AMOUNT_ITEMS = 18;

@Component({
  selector: 'app-cookbooks-page',
  templateUrl: './cookbooks-page.component.html',
  styleUrls: ['./cookbooks-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    CookbookCardComponent,
    ErrorDisplayComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbooksPageComponent {
  cookbooksLoading$ = new BehaviorSubject<boolean>(true);
  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: AMOUNT_ITEMS });

  cookbooks$ = merge(this.auth.user$).pipe(
    switchMap(() => {
      this.paginationOptions$.next({ ...this.paginationOptions$.value, page: 1 });

      return this.paginationOptions$.pipe(
        tap(() => this.cookbooksLoading$.next(true)),
        switchScan(
          (acc, paginationOptions) => {
            return this.api.cookbooks.getList(false, paginationOptions, [{ column: 'name', dir: 'asc' }]).pipe(
              map((response) => ({
                totalCookbooks: response.body!.meta.total,
                hasMoreItems: response.body!.meta.current_page < response.body!.meta.last_page,
                cookbooks: [...acc.cookbooks, ...response.body!.data],
              }))
            );
          },
          { totalCookbooks: 0, hasMoreItems: true, cookbooks: [] }
        ),
        tap(() => this.cookbooksLoading$.next(false))
      );
    }),

    shareReplay(1)
  );

  error$ = ApiService.handleRequestError(this.cookbooks$);

  constructor(private auth: AuthService, private api: ApiService) {}

  nextPage() {
    this.paginationOptions$.next({ ...this.paginationOptions$.value, page: this.paginationOptions$.value.page + 1 });
  }
}
