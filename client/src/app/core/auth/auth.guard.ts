import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot, UrlTree } from '@angular/router';
import { delay, map, Observable } from 'rxjs';
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
    if (this.auth.isAuthenticated) {
      return true;
    }

    const redirectUrl = routerState.url;

    return this.router.createUrlTree(['/login'], {
      queryParams: {
        'redirect-url': redirectUrl,
      },
    });
  }
}
