import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot, UrlTree } from '@angular/router';
import { Observable, combineLatest, map } from 'rxjs';
import { AuthService } from './auth.service';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate {
  constructor(private auth: AuthService, private router: Router) {}

  canActivate(
    activatedRoute: ActivatedRouteSnapshot,
    routerState: RouterStateSnapshot
  ): Observable<boolean | UrlTree> | Promise<boolean | UrlTree> | boolean | UrlTree {
    return combineLatest([this.auth.user$, this.auth.isInitialized$]).pipe(
      map(([user, isInitialized]) => {
        const mustBeAdmin = activatedRoute.data['mustBeAdmin'];

        if (user && isInitialized && (!mustBeAdmin || user.is_admin)) {
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
