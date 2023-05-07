import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, catchError, map, shareReplay, switchMap, switchScan, tap, throwError } from 'rxjs';
import { CookbookCardComponent } from 'src/app/components/cookbook-card/cookbook-card.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { PaginationOptions } from 'src/app/core/models/pagination-options';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';

const AMOUNT_ITEMS = 18;

@Component({
  selector: 'app-cookbooks-page',
  templateUrl: './cookbooks-page.component.html',
  styleUrls: ['./cookbooks-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
    CookbookCardComponent,
    ErrorDisplayComponent,
    SkeletonComponent,
    RepeatDirective,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbooksPageComponent {
  cookbooksLoading$ = new BehaviorSubject<boolean>(true);
  paginationOptions$ = new BehaviorSubject<PaginationOptions>({ page: 1, perPage: AMOUNT_ITEMS });

  cookbooks$ = this.auth.user$.pipe(
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
    catchError((error) => {
      this.cookbooksLoading$.next(false);

      return throwError(() => error);
    }),

    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.cookbooks$);

  constructor(private auth: AuthService, private api: ApiService) {}

  nextPage() {
    this.paginationOptions$.next({ ...this.paginationOptions$.value, page: this.paginationOptions$.value.page + 1 });
  }
}
