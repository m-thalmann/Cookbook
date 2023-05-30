import { coerceBooleanProperty as cdkCoerceBooleanProperty } from '@angular/cdk/coercion';

export function coerceBooleanProperty() {
  return function (target: any, key: string): void {
    const getter = function () {
      return target['__' + key];
    };

    const setter = function (next: any) {
      target['__' + key] = cdkCoerceBooleanProperty(next);
    };

    Object.defineProperty(target, key, {
      get: getter,
      set: setter,
      enumerable: true,
      configurable: true,
    });
  };
}
