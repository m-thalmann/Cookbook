import { ChangeDetectionStrategy, Component, OnDestroy, OnInit } from '@angular/core';
import { BehaviorSubject, Subscription, switchMap, tap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { CookbookWithCounts } from 'src/app/core/models/cookbook';
import { PaginationMeta } from 'src/app/core/models/pagination-meta';

const AMOUNT_ITEMS = 18;

@Component({
  selector: 'app-cookbooks-page',
  templateUrl: './cookbooks-page.component.html',
  styleUrls: ['./cookbooks-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CookbooksPageComponent implements OnInit, OnDestroy {
  private subSink = new Subscription();

  private page$ = new BehaviorSubject<number>(1);

  cookbooksLoading$ = new BehaviorSubject<boolean>(true);

  cookbooks$ = new BehaviorSubject<CookbookWithCounts[]>([]);
  paginationData$ = new BehaviorSubject<PaginationMeta | undefined>(undefined);

  constructor(private auth: AuthService, private api: ApiService) {}

  ngOnInit() {
    this.subSink.add(
      this.auth.user$.pipe().subscribe(() => {
        this.cookbooks$.next([]);
        this.paginationData$.next(undefined);
        this.page$.next(1);
      })
    );

    this.subSink.add(
      this.page$
        .pipe(
          tap(() => this.cookbooksLoading$.next(true)),
          switchMap((page) =>
            this.api.cookbooks.getList(false, { page: page, perPage: AMOUNT_ITEMS }, [{ column: 'name', dir: 'asc' }])
          ),
          tap(() => this.cookbooksLoading$.next(false))
        )
        .subscribe((response) => {
          this.cookbooks$.next(this.cookbooks$.value.concat(response.body!.data));
          this.paginationData$.next(response.body?.meta);
        })
    );
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }

  nextPage() {
    this.page$.next(this.page$.value + 1);
  }
}
