import { Pipe, PipeTransform } from '@angular/core';
import { TranslationService } from './translation.service';

@Pipe({
  name: 'translate',
  pure: false,
})
export class TranslationPipe implements PipeTransform {
  constructor(private translation: TranslationService) {}

  transform(value: string, replacements?: { [key: string]: string }): string {
    return this.translation.translate(value, replacements);
  }
}
