import { coerceBooleanProperty as cdkCoerceBooleanProperty } from '@angular/cdk/coercion';

export function CoerceBooleanProperty() {
  return function (target: any, key: string): void {
    const privatePropKey = Symbol();

    Reflect.defineProperty(target, key, {
      get(this: any) {
        return this[privatePropKey];
      },
      set(this: any, newValue: string) {
        this[privatePropKey] = cdkCoerceBooleanProperty(newValue);
      },
    });
  };
}
