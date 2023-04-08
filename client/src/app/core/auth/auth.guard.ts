import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot, UrlTree } from '@angular/router';
import { combineLatest, map, Observable } from 'rxjs';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate {
  constructor(private auth: AuthService, private router: Router) {}

  canActivate(
    _: ActivatedRouteSnapshot,
    routerState: RouterStateSnapshot
  ): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {
    return combineLatest([this.auth.isAuthenticated$, this.auth.isInitialized$]).pipe(
      map(([isAuthenticated, isInitialized]) => {
        if (isAuthenticated && isInitialized) {
          return true;
        }

        return this.router.createUrlTree(['/login'], {
          queryParams: {
            'redirect-url': routerState.url,
          },
        });
      })
    );
  }
}
