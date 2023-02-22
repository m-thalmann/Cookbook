import { AfterViewInit, ChangeDetectionStrategy, Component, ElementRef, OnDestroy, ViewChild } from '@angular/core';
import { of, Subscription, switchMap } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { createIntersectionObserver } from 'src/app/core/helpers/intersection-observer';
import { PaginationMeta } from 'src/app/core/models/pagination-meta';

const AMOUNT_ITEMS = 12;

@Component({
  selector: 'app-home-page',
  templateUrl: './home-page.component.html',
  styleUrls: ['./home-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class HomePageComponent implements AfterViewInit, OnDestroy {
  subSink: Subscription = new Subscription();

  @ViewChild('searchBarContainer', { read: ElementRef }) searchBarContainer!: ElementRef;

  categories$ = this.auth.user$.pipe(switchMap(() => this.api.categories.getList()));
  recipes$ = this.auth.user$.pipe(
    switchMap(() => this.api.recipes.getList({ pagination: { page: 1, perPage: AMOUNT_ITEMS } }))
  );
  cookbooks$ = this.auth.user$.pipe(
    switchMap((user) => {
      if (user === null) {
        return of(null);
      }

      return this.api.cookbooks.getList(false, { page: 1, perPage: AMOUNT_ITEMS });
    })
  );

  constructor(private api: ApiService, public auth: AuthService) {}

  ngAfterViewInit() {
    this.subSink.add(
      createIntersectionObserver(this.searchBarContainer, { threshold: [1] }).subscribe((e) => {
        this.searchBarContainer.nativeElement.classList.toggle('stuck', !e);
      })
    );
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }

  getRemainingItems(meta?: PaginationMeta) {
    if (!meta) {
      return 0;
    }

    return meta.total - meta.count;
  }
}
