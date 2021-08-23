import { AfterViewInit, Component } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { NavigationEnd, Router } from '@angular/router';
import { fromEvent } from 'rxjs';
import { map, share, throttleTime } from 'rxjs/operators';
import { UserService } from '../core/auth/user.service';
import { LoginRegisterDialogComponent } from './components/login-register-dialog/login-register-dialog.component';
import { SettingsDialogComponent } from './components/settings-dialog/settings-dialog.component';

@Component({
  selector: 'cb-layout',
  templateUrl: './layout.component.html',
  styleUrls: ['./layout.component.scss'],
})
export class LayoutComponent implements AfterViewInit {
  showMenu = false;
  smallHeader = false;

  constructor(public user: UserService, private dialog: MatDialog, private router: Router) {
    this.router.events.subscribe((event) => {
      if (event instanceof NavigationEnd) {
        this.showMenu = false;
      }
    });
  }

  ngAfterViewInit() {
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
      });
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
}
