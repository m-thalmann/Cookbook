import { AfterViewInit, Component, NgZone, OnDestroy } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { NavigationEnd, Router } from '@angular/router';
import { fromEvent } from 'rxjs';
import { map, share, throttleTime } from 'rxjs/operators';
import { UserService } from '../core/auth/user.service';
import { SubSink } from '../core/functions';
import { TranslationService } from '../core/i18n/translation.service';
import { SeoService } from '../core/services/seo.service';
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

  private subSink = new SubSink();

  constructor(
    public user: UserService,
    private dialog: MatDialog,
    private router: Router,
    private seo: SeoService,
    private ngZone: NgZone,
    public translation: TranslationService
  ) {
    this.subSink.push(
      this.router.events.subscribe((event) => {
        if (event instanceof NavigationEnd) {
          this.showMenu = false;

          this.seo.generateTags();
        }
      })
    );
  }

  ngAfterViewInit() {
    this.ngZone.runOutsideAngular(() => {
      this.subSink.push(
        fromEvent(window, 'scroll')
          .pipe(
            throttleTime(10),
            map(() => window.pageYOffset),
            share()
          )
          .subscribe((val) => {
            let smallHeader = this.smallHeader;
            if (this.smallHeader) {
              smallHeader = val > 180;
            } else {
              smallHeader = val > 200;
            }

            if (smallHeader !== this.smallHeader) {
              this.ngZone.run(() => {
                this.smallHeader = smallHeader;
              });
            }
          })
      );
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

  /**
   * Function needed to forcefully reload recipe-create page after history.push(...)
   */
  async navigateCreateRecipe() {
    if (this.router.url.localeCompare('/recipes/create') === 0) {
      await this.router.navigateByUrl('/reload', {
        // non-existent location, does not matter
        skipLocationChange: true,
      });
      await this.router.navigateByUrl('/recipes/create');
    }
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
