import { AfterViewInit, ChangeDetectionStrategy, Component, ElementRef, OnDestroy, ViewChild } from '@angular/core';
import { filter, Subscription, switchMap } from 'rxjs';
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

  categories$ = this.auth.isAuthenticated$.pipe(switchMap(() => this.api.categories.getList()));
  recipes$ = this.auth.isAuthenticated$.pipe(
    switchMap(() => this.api.recipes.getList({ pagination: { perPage: AMOUNT_ITEMS } }))
  );
  cookbooks$ = this.auth.isAuthenticated$.pipe(
    filter((authenticated) => authenticated),
    switchMap(() => this.api.cookbooks.getList(false, { perPage: AMOUNT_ITEMS }))
  );

  categoriesClampAmount: number = 5;

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

  revealMoreCategories() {
    this.categoriesClampAmount += 5;
  }

  getRemainingItems(meta?: PaginationMeta) {
    if (!meta) {
      return 0;
    }

    return meta.total - meta.count;
  }
}
