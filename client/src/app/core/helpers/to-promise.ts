import { Observable, first, lastValueFrom } from 'rxjs';

export const toPromise = async <T>(observable: Observable<T>, exceptionAsRejected = false): Promise<T> => {
  const promise = lastValueFrom(observable.pipe(first()));

  if (!exceptionAsRejected) {
    return promise;
  }

  try {
    return await promise;
  } catch (error) {
    return await Promise.reject(error);
  }
};
