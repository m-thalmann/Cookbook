import { Observable, first, lastValueFrom } from 'rxjs';

export const toPromise = <T>(observable: Observable<T>): Promise<T> => {
  return lastValueFrom(observable.pipe(first()));
};
