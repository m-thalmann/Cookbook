import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from './translation.service';

@Pipe({
  name: 'translate',
  pure: false,
})
export class TranslationPipe implements PipeTransform {
  private _translated: string | null = null;

  private _lastValue: string | null = null;
  private _lastReplacements: string | null = null;
  private _lastLanguage: string | null = null;

  constructor(private translation: TranslationService) {}

  transform(value: string, replacements?: { [key: string]: string }): string {
    const stringifiedReplacements = JSON.stringify(replacements);

    let changes = false;

    changes = changes || this._lastValue === null;
    changes = changes || this._lastValue !== value;
    changes = changes || this._lastReplacements !== stringifiedReplacements;
    changes = changes || this._lastLanguage !== this.translation.language;

    if (changes) {
      this._translated = this.translation.translate(value, replacements);
      this._lastValue = value;
      this._lastReplacements = stringifiedReplacements;
      this._lastLanguage = this.translation.language;
    }

    return this._translated || '';
  }
}
