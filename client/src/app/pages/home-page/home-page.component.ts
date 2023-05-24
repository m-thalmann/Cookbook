import { CommonModule } from '@angular/common';
import { AfterViewInit, ChangeDetectionStrategy, Component, ElementRef, OnDestroy, ViewChild } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { RouterLink } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { Subscription, of, shareReplay, switchMap } from 'rxjs';
import { CategoryChipListComponent } from 'src/app/components/category-chip-list/category-chip-list.component';
import { CookbookCardComponent } from 'src/app/components/cookbook-card/cookbook-card.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { PageSectionComponent } from 'src/app/components/page-section/page-section.component';
import { RecipeCardComponent } from 'src/app/components/recipe-card/recipe-card.component';
import { SearchBarComponent } from 'src/app/components/search-bar/search-bar.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { createIntersectionObserver } from 'src/app/core/helpers/intersection-observer';
import { CookbookWithCounts } from 'src/app/core/models/cookbook';
import { PaginationMeta } from 'src/app/core/models/pagination-meta';
import { ListRecipe } from 'src/app/core/models/recipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';

@Component({
  selector: 'app-home-page',
  templateUrl: './home-page.component.html',
  styleUrls: ['./home-page.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    SearchBarComponent,
    PageSectionComponent,
    CategoryChipListComponent,
    RecipeCardComponent,
    CookbookCardComponent,
    ErrorDisplayComponent,
    RepeatDirective,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class HomePageComponent implements AfterViewInit, OnDestroy {
  readonly amountItems = 6;

  subSink: Subscription = new Subscription();

  @ViewChild('searchBarContainer', { read: ElementRef }) searchBarContainer!: ElementRef;

  categories$ = this.auth.user$.pipe(
    switchMap(() => this.api.categories.getList(false, 'desc')),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );
  recipes$ = this.auth.user$.pipe(
    switchMap(() =>
      this.api.recipes.getList({
        sort: [{ column: 'created_at', dir: 'desc' }],
        pagination: { page: 1, perPage: this.amountItems },
      })
    ),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );
  cookbooks$ = this.auth.user$.pipe(
    switchMap((user) => {
      if (user === null) {
        return of(null);
      }

      return this.api.cookbooks.getList(false, { page: 1, perPage: this.amountItems });
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  categoriesError$ = this.api.handleRequestError(this.categories$);
  recipesError$ = this.api.handleRequestError(this.recipes$);
  cookbooksError$ = this.api.handleRequestError(this.cookbooks$);

  constructor(private api: ApiService, public auth: AuthService) {}

  ngAfterViewInit() {
    this.subSink.add(
      createIntersectionObserver(this.searchBarContainer, { threshold: [1] }).subscribe((e) => {
        this.searchBarContainer.nativeElement.classList.toggle('stuck', !e);
      })
    );
  }

  getRemainingItems(meta?: PaginationMeta) {
    if (!meta) {
      return '';
    }

    return meta.total - meta.count;
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }

  trackByRecipe(index: number, recipe: ListRecipe) {
    return recipe.id;
  }

  trackByCookbook(index: number, cookbook: CookbookWithCounts) {
    return cookbook.id;
  }
}
