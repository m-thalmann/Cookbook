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
  canActivate(_: ActivatedRouteSnapshot, state: RouterStateSnapshot) {
    const url = state.url.split('?')[0];

    if (this.user.isLoggedin || !url.startsWith('/my')) {
      return true;
    }

    this.snackBar.open('You need to login to view this page!', 'OK', {
      panelClass: 'action-warn',
    });

    this.router.navigateByUrl('/home');

    return false;
  }
}
