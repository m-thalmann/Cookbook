import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'clampArray',
  standalone: true,
})
export class ClampArrayPipe implements PipeTransform {
  transform<T>(array: T[] | undefined | null, limitAmount: number | null) {
    if (typeof array === 'undefined' || array === null) {
      return array;
    }

    let items = array;

    if (limitAmount !== null && limitAmount > 0) {
      items = array.slice(0, limitAmount);
    }

    return {
      items,
      overflowAmount: array.length - items.length,
    };
  }
}
