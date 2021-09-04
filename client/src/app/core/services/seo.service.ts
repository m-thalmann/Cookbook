import { Injectable } from '@angular/core';
import { Meta, Title } from '@angular/platform-browser';
import { ActivatedRoute } from '@angular/router';

const SITE_NAME = 'Cookbook';
const DESCRIPTION = 'A webapp for your recipes';
const KEYWORDS = 'cookbook,recipes,cooking';

@Injectable({
  providedIn: 'root',
})
export class SeoService {
  constructor(private meta: Meta, private titleService: Title, private route: ActivatedRoute) {}

  /**
   * Generates the seo tags (and title) for the currently active route.
   * The title-information are contained in the route-data
   */
  generateTags() {
    const baseRoute = this.route.snapshot;
    let route = baseRoute.firstChild;

    this.setTitle('Cookbook', '');

    do {
      route = route?.firstChild || null;

      if (!route) return;

      if (route.data.title) {
        this.setTitle(`${route.data.title}`);
        break;
      } else if (route.data.titleFromParam) {
        this.setTitle(`${route.params[route.data.titleFromParam]}`);
        break;
      }
    } while (route.firstChild);

    this.setDescription(DESCRIPTION);
    this.setImage(this.defaultImage);

    this.meta.updateTag({ name: 'twitter:site', content: SITE_NAME });
    this.meta.updateTag({ property: 'og:site_name', content: SITE_NAME });

    this.meta.updateTag({ name: 'keywords', content: KEYWORDS });
  }

  setTitle(title: string, suffix = ' - Cookbook') {
    title += suffix;

    this.titleService.setTitle(title);
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

  get defaultImage() {
    return `${location.origin}/assets/images/cookbook.png`;
  }
}
