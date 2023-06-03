import { formatDate } from '@angular/common';
import { Inject, LOCALE_ID, Pipe, PipeTransform } from '@angular/core';
import { TranslocoService } from '@ngneat/transloco';

@Pipe({
  name: 'i18nDate',
  standalone: true,
})
export class I18nDatePipe implements PipeTransform {
  constructor(private transloco: TranslocoService, @Inject(LOCALE_ID) private locale: string) {}

  transform(value: Date | string | number, withTime = false): unknown {
    const format = this.transloco.translate(withTime ? 'dateTimeFormat' : 'dateFormat');

    return formatDate(value, format, this.locale);
  }
}

