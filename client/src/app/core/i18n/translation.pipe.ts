import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from './translation.service';

@Pipe({
  name: 'translate',
  pure: false,
})
export class TranslationPipe implements PipeTransform {
  private _translated: string | null = null;
  private _lastValue: string | null = null;
  private _lastLanguage: string | null = null;

  constructor(private translation: TranslationService) {}

  transform(value: string, replacements?: { [key: string]: string }): string {
    if (this._lastValue === null || this._lastValue !== value || this._lastLanguage !== this.translation.language) {
      this._translated = this.translation.translate(value, replacements);
      this._lastValue = value;
      this._lastLanguage = this.translation.language;
    }

    return this._translated || '';
  }
}
