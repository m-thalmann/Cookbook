import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'clampArray',
})
export class ClampArrayPipe implements PipeTransform {
  transform(array: any[] | undefined | null, limitAmount: number) {
    if (typeof array === 'undefined' || array === null) {
      return array;
    }

    let overflowAmount = array.splice(limitAmount).length;

    return {
      items: array,
      overflowAmount,
    };
  }
}

