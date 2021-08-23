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
   * @param _
   * @param state
   * @returns whether the user is allowed to request this route
   */
  canActivate(_: ActivatedRouteSnapshot, state: RouterStateSnapshot) {
    if (this.user.isLoggedin) {
      return true;
    }

    this.snackBar.open('You need to log in to view this page!', 'OK', {
      panelClass: 'action-warn',
    });

    this.router.navigateByUrl('/home');

    return false;
  }
}
