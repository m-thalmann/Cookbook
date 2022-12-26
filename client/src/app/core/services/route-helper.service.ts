import { Location } from '@angular/common';
import { Injectable } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';

@Injectable({
  providedIn: 'root',
})
export class RouteHelperService {
  constructor(private location: Location, private router: Router) {}

  getRouteLeaf(route: ActivatedRoute | null) {
    let leaf = route;

    while (leaf?.firstChild) {
      leaf = leaf.firstChild;
    }

    return leaf;
  }

  navigateBack() {
    if (document.referrer.length === 0 || document.referrer.indexOf(window.location.host) !== -1) {
      this.location.back();
    } else {
      this.router.navigateByUrl('/');
    }
  }
}
