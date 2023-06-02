import { Injectable } from '@angular/core';
import { PreloadingStrategy, Route } from '@angular/router';
import { Observable, of, switchMap, timer } from 'rxjs';

const DEFAULT_DELAY_SECONDS = 2;

@Injectable({
  providedIn: 'root',
})
export class ConditionalPreloadStrategy implements PreloadingStrategy {
  preload(route: Route, load: () => Observable<any>): Observable<any> {
    if (!route.data || !route.data['preload']) {
      return of(null);
    }

    const delaySeconds = route.data['preloadDelaySeconds'] ?? DEFAULT_DELAY_SECONDS;

    if (delaySeconds <= 0) {
      return load();
    }

    return timer(delaySeconds * 1000).pipe(switchMap(() => load()));
  }
}
