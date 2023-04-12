import { OperatorFunction, catchError, throwError } from 'rxjs';
import { HandledError } from '../helpers/handled-error';

export function handledErrorInterceptor<T>(): OperatorFunction<T, T> {
  return catchError((error: unknown) => throwError(() => new HandledError(error)));
}
