import { Injectable, OnDestroy } from '@angular/core';
import { MatPaginatorIntl } from '@angular/material/paginator';
import { TranslocoService } from '@ngneat/transloco';
import { Subject, Subscription } from 'rxjs';

interface TranslationObject {
  next: string;
  previous: string;
  first: string;
  last: string;
  itemsPerPage: string;
  rangeLabel: string;
}

@Injectable()
export class CustomPaginatorIntl implements MatPaginatorIntl, OnDestroy {
  private subSink = new Subscription();

  private translations$ = this.transloco.selectTranslateObject<TranslationObject>('pagination');

  changes = new Subject<void>();

  firstPageLabel!: string;
  itemsPerPageLabel!: string;
  lastPageLabel!: string;

  nextPageLabel!: string;
  previousPageLabel!: string;

  constructor(private transloco: TranslocoService) {
    this.subSink.add(
      this.translations$.subscribe((translations) => {
        this.setTranslations(translations);
        this.changes.next();
      })
    );
  }

  getRangeLabel(page: number, pageSize: number, length: number): string {
    const amountPages = length > 0 ? Math.ceil(length / pageSize) : 0;

    if (length === 0) {
      page = 1;
    }

    return this.transloco.translate('pagination.rangeLabel', { page: page + 1, pages: amountPages });
  }

  private setTranslations(translations: TranslationObject) {
    this.firstPageLabel = translations.first;
    this.itemsPerPageLabel = translations.itemsPerPage;
    this.lastPageLabel = translations.last;

    this.nextPageLabel = translations.next;
    this.previousPageLabel = translations.previous;
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
