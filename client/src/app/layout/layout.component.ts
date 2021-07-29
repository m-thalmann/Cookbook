import { AfterViewInit, Component } from '@angular/core';
import { fromEvent } from 'rxjs';
import { map, share, throttleTime } from 'rxjs/operators';
import { UserService } from '../core/auth/user.service';

@Component({
  selector: 'cb-layout',
  templateUrl: './layout.component.html',
  styleUrls: ['./layout.component.scss'],
})
export class LayoutComponent implements AfterViewInit {
  showMenu = false;
  smallHeader = false;

  constructor(private user: UserService) {}

  ngAfterViewInit() {
    fromEvent(window, 'scroll')
      .pipe(
        throttleTime(10),
        map(() => window.pageYOffset),
        share()
      )
      .subscribe((val) => {
        this.smallHeader = val > 200;
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
      var currentScroll = document.documentElement.scrollTop || document.body.scrollTop;
      if (currentScroll > 0) {
        window.requestAnimationFrame(smoothscroll);
        window.scrollTo(0, currentScroll - currentScroll / 8);
      }
    })();
  }
}
