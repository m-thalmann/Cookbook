import { AfterViewInit, Component, OnDestroy } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { NavigationEnd, Router } from '@angular/router';
import { fromEvent, Subscription } from 'rxjs';
import { map, share, throttleTime } from 'rxjs/operators';
import { UserService } from '../core/auth/user.service';
import { SeoService } from '../core/seo/seo.service';
import { LoginRegisterDialogComponent } from './components/login-register-dialog/login-register-dialog.component';
import { SettingsDialogComponent } from './components/settings-dialog/settings-dialog.component';

@Component({
  selector: 'cb-layout',
  templateUrl: './layout.component.html',
  styleUrls: ['./layout.component.scss'],
})
export class LayoutComponent implements AfterViewInit, OnDestroy {
  showMenu = false;
  smallHeader = false;

  private subscriptions: Subscription[] = [];

  constructor(public user: UserService, private dialog: MatDialog, private router: Router, private seo: SeoService) {
    this.subscriptions.push(
      this.router.events.subscribe((event) => {
        if (event instanceof NavigationEnd) {
          this.showMenu = false;

          this.seo.generateTags();
        }
      })
    );
  }

  ngAfterViewInit() {
    this.subscriptions.push(
      fromEvent(window, 'scroll')
        .pipe(
          throttleTime(10),
          map(() => window.pageYOffset),
          share()
        )
        .subscribe((val) => {
          if (this.smallHeader) {
            this.smallHeader = val > 180;
          } else {
            this.smallHeader = val > 200;
          }
        })
    );
  }

  get isLoggedin() {
    return this.user.isLoggedin;
  }

  toggleMenu() {
    this.showMenu = !this.showMenu;
  }

  scrollToTop() {
    (function smoothscroll() {
      let currentScroll = document.documentElement.scrollTop || document.body.scrollTop;
      if (currentScroll > 0) {
        window.requestAnimationFrame(smoothscroll);
        window.scrollTo(0, currentScroll - currentScroll / 8);
      }
    })();
  }

  showSettingsDialog() {
    this.dialog.open(SettingsDialogComponent);
  }

  showLoginRegisterDialog() {
    this.dialog.open(LoginRegisterDialogComponent, {
      width: '400px',
    });
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
