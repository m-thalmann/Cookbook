import { DatePipe } from '@angular/common';
import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from '../i18n/translation.service';

@Pipe({
  name: 'localizedDate',
  pure: false,
})
export class LocalizedDatePipe implements PipeTransform {
  private datePipe: DatePipe;

  private _transformed: string | null = null;

  private _lastLanguage: string | null = null;
  private _lastValue: string | null = null;

  constructor(private translation: TranslationService) {
    this.datePipe = new DatePipe('en-US');
  }

  transform(value: any, time = false): string | null {
    if (this._lastLanguage !== this.translation.language || this._lastValue !== value) {
      this._transformed = this.datePipe.transform(
        value,
        this.translation.translate(time ? 'date_time_format' : 'date_format')
      );
    }

    return this._transformed;
  }
}
