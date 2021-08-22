import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'amount',
})
export class AmountPipe implements PipeTransform {
  transform(value: string | number): string {
    if (typeof value === 'string') {
      value = parseFloat(value);
    }

    let floorValue = Math.floor(value);
    let decimals = value - floorValue;

    let floorValueStr = floorValue > 0 ? floorValue.toString() : '';

    if (decimals === 0.25) {
      return floorValueStr + '¼';
    }
    if (decimals === 0.33) {
      return floorValueStr + '⅓';
    }
    if (decimals === 0.5) {
      return floorValueStr + '½';
    }
    if (decimals === 0.67) {
      return floorValueStr + '⅔';
    }
    if (decimals === 0.75) {
      return floorValueStr + '¾';
    }

    return value.toString();
  }
}
