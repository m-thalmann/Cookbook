import { Injectable, OnDestroy } from '@angular/core';
import { ActivatedRouteSnapshot, CanActivate, Router, RouterStateSnapshot } from '@angular/router';
import { Observable } from 'rxjs';
import { SubSink } from '../functions';
import { SnackbarService } from '../services/snackbar.service';
import { UserService } from './user.service';

@Injectable({
  providedIn: 'root',
})
export class AuthGuard implements CanActivate, OnDestroy {
  private subSink = new SubSink();

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
    return new Observable<boolean>((subscriber) => {
      subscriber.next(this.isAuthorized(route));

      this.subSink.push(
        this.user.userChanged.subscribe(() => {
          subscriber.next(this.isAuthorized(route));
        })
      );
    });
  }

  private isAuthorized(route: ActivatedRouteSnapshot) {
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

  ngOnDestroy() {
    this.subSink.clear();
  }
}
