import { MatPaginatorIntl } from '@angular/material/paginator';
import { TranslationService } from '../i18n/translation.service';

export class MatPaginatorIntlCustom extends MatPaginatorIntl {
  constructor(private translation: TranslationService) {
    super();

    this.translation.languageChanged.subscribe(() => {
      this.setLabels();
      this.changes.next();
    });

    this.setLabels();
  }

  private setLabels() {
    this.itemsPerPageLabel = this.translation.translate('paginator.items_per_page');
    this.nextPageLabel = this.translation.translate('paginator.next_page');
    this.previousPageLabel = this.translation.translate('paginator.previous_page');
    this.firstPageLabel = this.translation.translate('paginator.first_page');
    this.lastPageLabel = this.translation.translate('paginator.last_page');
  }

  getRangeLabel = (page: number, pageSize: number, length: number) => {
    const paginatorOf = this.translation.translate('paginator.of');

    length = Math.max(length, 0);

    if (length === 0 || pageSize === 0) {
      return `0 ${paginatorOf} ${length}`;
    }

    const startIndex = page * pageSize;
    const endIndex = startIndex < length ? Math.min(startIndex + pageSize, length) : startIndex + pageSize;

    return `${startIndex + 1} - ${endIndex} ${paginatorOf} ${length}`;
  };
}
