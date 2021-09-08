import { Injectable } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot } from '@angular/router';
import { SnackbarService } from '../services/snackbar.service';
import { UserService } from './user.service';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate {
  constructor(private user: UserService, private router: Router, private snackbar: SnackbarService) {}

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
        error = 'messages.auth.route.unauthorized';
      }
    }

    this.snackbar.error(error || 'messages.auth.route.login_required');

    this.router.navigateByUrl('/home');

    return false;
  }
}
