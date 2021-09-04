import { Route } from '@angular/compiler/src/core';
import { Injectable } from '@angular/core';
import { PreloadingStrategy } from '@angular/router';
import { Observable, of, timer } from 'rxjs';
import { mergeMap } from 'rxjs/operators';

@Injectable({
  providedIn: 'root',
})
export class PreloadService implements PreloadingStrategy {
  preload(route: Route, load: () => Observable<any>): Observable<any> {
    const _route = route as any;

    let preload = false;

    if (_route.data && _route.data.preload) {
      preload = _route.data.preload;
    }

    if (!preload) {
      return of(null);
    }

    if (_route.data && _route.data.delay) {
      return timer(2000).pipe(mergeMap(() => load()));
    }
    return load();
  }
}
