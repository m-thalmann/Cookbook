import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot } from '@angular/router';
import { UserService } from './user.service';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate {
  constructor(private user: UserService, private router: Router, private snackBar: MatSnackBar) {}

  /**
   * Returns whether the currently authenticated user is allowed to request this route
   * If the user is not allowed, it will be redirected to the home page and shown an error
   *
   * @param route
   * @param state
   * @returns whether the user is allowed to request this route
   */
  canActivate(route: ActivatedRouteSnapshot, state: RouterStateSnapshot) {
    // const redirectUrl = state.url;

    // const url = redirectUrl.split('?')[0];

    // if (url.startsWith('/admin')) {
    //   if (url === '/admin/users') {
    //     if (this.user.hasRole(Role.Admin)) return true;
    //   } else {
    //     if (this.user.hasRole(Role.Gamemanager)) return true;
    //   }
    // } else if (this.user.isLoggedin) {
    //   return true;
    // }

    // let redirect: string | UrlTree;

    // if (this.user.isLoggedin) {
    //   this.snackBar.open('You are not authorized to view this page!', 'OK', {
    //     panelClass: 'action-warn',
    //   });

    //   redirect = '/home';
    // } else {
    //   redirect = this.router.createUrlTree(['/login'], {
    //     queryParams: {
    //       redirectUrl,
    //     },
    //   });
    // }

    // this.router.navigateByUrl(redirect);

    // return false;
    return true; // TODO: implement
  }
}
