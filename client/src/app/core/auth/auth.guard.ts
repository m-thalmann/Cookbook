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
   * @param _
   * @returns whether the user is allowed to request this route
   */
  canActivate(route: ActivatedRouteSnapshot, _: RouterStateSnapshot) {
    let error: string | null = null;

    if (this.user.isLoggedin) {
      if (!route.data.admin || this.user.user?.isAdmin) {
        return true;
      } else {
        error = 'You are not authorized to view this page!';
      }
    }

    this.snackBar.open(error || 'You need to log in to view this page!', 'OK', {
      panelClass: 'action-warn',
    });

    this.router.navigateByUrl('/home');

    return false;
  }
}
