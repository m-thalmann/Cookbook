import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { ActivatedRoute, NavigationEnd, Router, RouterLink, RouterOutlet } from '@angular/router';
import { Observable, combineLatest, filter, map, of, startWith, switchMap } from 'rxjs';
import { AuthService } from 'src/app/core/auth/auth.service';
import { User } from 'src/app/core/models/user';
import { RouteHelperService } from 'src/app/core/services/route-helper.service';
import { LayoutAddActionFabComponent } from './components/layout-add-action-fab/layout-add-action-fab.component';
import { LayoutDefaultNavbarComponent } from './components/layout-default-navbar/layout-default-navbar.component';

@Component({
  selector: 'app-layout-default',
  templateUrl: './layout-default.component.html',
  styleUrls: ['./layout-default.component.scss'],
  standalone: true,
  imports: [
    CommonModule,
    RouterOutlet,
    MatButtonModule,
    MatIconModule,
    MatMenuModule,
    RouterLink,
    LayoutAddActionFabComponent,
    LayoutDefaultNavbarComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutDefaultComponent {
  private routeData$ = this.router.events.pipe(
    filter((event) => event instanceof NavigationEnd),
    startWith(this.activatedRoute.snapshot.firstChild?.data || {}),
    switchMap(() => {
      const leafRoute = this.routeHelper.getRouteLeaf(this.activatedRoute);

      if (leafRoute) {
        return leafRoute.data;
      }

      return of({});
    })
  );

  isOverlayRoute$: Observable<boolean> = this.routeData$.pipe(map((data) => data['isOverlay'] || false));
  showAddButton$: Observable<boolean> = combineLatest([
    this.routeData$.pipe(map((data) => data['showAddButton'] || false)),
    this.auth.isAuthenticated$,
  ]).pipe(map(([showAddButton, isAuthenticated]) => showAddButton && isAuthenticated));

  constructor(
    public auth: AuthService,
    private router: Router,
    private activatedRoute: ActivatedRoute,
    private routeHelper: RouteHelperService
  ) {}

  get loginQueryParams() {
    return {
      'redirect-url': this.router.url,
    };
  }

  getUserInitials(user: User) {
    const initials = user.name.split(' ').map((word) => word[0]);

    if (initials.length > 1) {
      return initials[0] + initials[initials.length - 1];
    }

    return initials[0];
  }

  doLogout() {
    this.auth.logout(true);
  }
}
