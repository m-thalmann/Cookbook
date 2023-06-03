import { formatDate } from '@angular/common';
import { Pipe, PipeTransform } from '@angular/core';
import { TranslocoService } from '@ngneat/transloco';

@Pipe({
  name: 'i18nDate',
  standalone: true,
})
export class I18nDatePipe implements PipeTransform {
  constructor(private transloco: TranslocoService) {}

  transform(value: Date | string | number, withTime = false): unknown {
    const format = this.transloco.translate(withTime ? 'dateTimeFormat' : 'dateFormat');

    return formatDate(value, format, this.transloco.getActiveLang());
  }
}

