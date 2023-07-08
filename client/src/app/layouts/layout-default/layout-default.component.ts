import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatMenuModule } from '@angular/material/menu';
import { ActivatedRoute, Data, NavigationEnd, Router, RouterLink, RouterOutlet } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { Observable, combineLatest, filter, map, of, startWith, switchMap } from 'rxjs';
import { AuthService } from 'src/app/core/auth/auth.service';
import { User } from 'src/app/core/models/user';
import { RouteHelperService } from 'src/app/core/services/route-helper.service';
import { CreateCookbookDialogComponent } from 'src/app/pages/cookbooks/components/create-cookbook-dialog/create-cookbook-dialog.component';
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
    TranslocoModule,
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
  private routeData$: Observable<Data> = this.router.events.pipe(
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
    private routeHelper: RouteHelperService,
    private dialog: MatDialog
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

  openCreateCookbookDialog() {
    this.dialog.open(CreateCookbookDialogComponent);
  }
}
