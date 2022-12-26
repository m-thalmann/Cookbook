import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatBottomSheet } from '@angular/material/bottom-sheet';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { filter, map, Observable, of, startWith, switchMap } from 'rxjs';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RouteHelperService } from 'src/app/core/services/route-helper.service';
import { AccountMenuBottomSheetComponent } from './components/account-menu-bottom-sheet/account-menu-bottom-sheet.component';

@Component({
  selector: 'app-layout-default',
  templateUrl: './layout-default.component.html',
  styleUrls: ['./layout-default.component.scss'],
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

  isOverlayRoute$ = this.routeData$.pipe(map((data) => data['isOverlay'] || false)) as Observable<boolean>;

  constructor(
    public auth: AuthService,
    private bottomSheet: MatBottomSheet,
    private router: Router,
    private activatedRoute: ActivatedRoute,
    private routeHelper: RouteHelperService
  ) {}

  get loginQueryParams() {
    return {
      'redirect-url': this.router.url,
    };
  }

  openAccountMenu() {
    this.bottomSheet.open(AccountMenuBottomSheetComponent);
  }
}
