import { ElementRef } from '@angular/core';
import { distinctUntilChanged, map, mergeMap, Observable } from 'rxjs';

export const createIntersectionObserver = (element: ElementRef, options?: IntersectionObserverInit) => {
  return new Observable<IntersectionObserverEntry[]>((observer) => {
    const intersectionObserver = new IntersectionObserver((entries) => {
      observer.next(entries);
    }, options);

    intersectionObserver.observe(element.nativeElement);

    return () => {
      intersectionObserver.disconnect();
    };
  }).pipe(
    mergeMap((entries: IntersectionObserverEntry[]) => entries),
    map((entry) => entry.isIntersecting),
    distinctUntilChanged()
  );
};
