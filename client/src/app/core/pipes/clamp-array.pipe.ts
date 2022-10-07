import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'clampArray',
})
export class ClampArrayPipe implements PipeTransform {
  transform(array: any[] | undefined | null, limitAmount: number | null) {
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

