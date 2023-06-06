import { Injectable, OnDestroy } from '@angular/core';
import { Meta, Title } from '@angular/platform-browser';
import { ActivatedRoute, ActivatedRouteSnapshot, NavigationEnd, Router } from '@angular/router';
import { TranslocoService } from '@ngneat/transloco';
import { Subscription, combineLatest, filter, map, startWith } from 'rxjs';
import { RouteHelperService } from './route-helper.service';

const SITE_NAME = 'Cookbook';

@Injectable({
  providedIn: 'root',
})
export class SeoService implements OnDestroy {
  private subSink = new Subscription();

  private activatedRouteSnapshot$ = this.router.events.pipe(
    filter((event) => event instanceof NavigationEnd),
    startWith(this.activatedRoute.snapshot),
    map(() => this.activatedRoute.snapshot)
  );

  constructor(
    private meta: Meta,
    private title: Title,
    private routeHelper: RouteHelperService,
    private transloco: TranslocoService,
    private activatedRoute: ActivatedRoute,
    private router: Router
  ) {}

  initialize() {
    this.subSink.add(
      combineLatest([
        this.activatedRouteSnapshot$,
        this.transloco.events$.pipe(filter((event) => event.type === 'translationLoadSuccess')),
      ]).subscribe(([activatedRoute, _]) => {
        this.generateTags(activatedRoute);
      })
    );
  }

  generateTags(route: ActivatedRouteSnapshot) {
    const childRoute = this.routeHelper.getRouteLeaf(route);

    if (childRoute.data['title']) {
      this.setTitle(this.transloco.translate(childRoute.data['title']));
    } else {
      this.setTitle(SITE_NAME, '');
    }

    this.setDescription(this.transloco.translate('about.description'));
    this.setImage(this.defaultImage);

    this.meta.updateTag({ name: 'twitter:site', content: SITE_NAME });
    this.meta.updateTag({ property: 'og:site_name', content: SITE_NAME });
  }

  setTitle(title: string, suffix = ' - ' + SITE_NAME) {
    title += suffix;

    this.title.setTitle(title);
    this.meta.updateTag({ name: 'twitter:title', content: title });
    this.meta.updateTag({ property: 'og:title', content: title });
  }

  setDescription(description: string) {
    this.meta.updateTag({ name: 'twitter:description', content: description });
    this.meta.updateTag({ property: 'og:description', content: description });
    this.meta.updateTag({ property: 'description', content: description });
  }

  setImage(imageUrl: string) {
    this.meta.updateTag({ name: 'twitter:image', content: imageUrl });
    this.meta.updateTag({ property: 'og:image', content: imageUrl });
  }

  private get defaultImage() {
    return `${location.origin}/assets/images/app-icons/icon-128x128.png`;
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}

