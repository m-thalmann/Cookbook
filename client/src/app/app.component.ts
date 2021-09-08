import { Component, OnDestroy } from '@angular/core';
import { MatIconRegistry } from '@angular/material/icon';
import { DomSanitizer } from '@angular/platform-browser';
import { Subscription } from 'rxjs';
import { TranslationService } from './core/i18n/translation.service';
import { SeoService } from './core/services/seo.service';

@Component({
  selector: 'cb-root',
  template: `<router-outlet></router-outlet>`,
  styles: [],
})
export class AppComponent implements OnDestroy {
  private subscriptions: Subscription[] = [];

  constructor(
    private matIconRegistry: MatIconRegistry,
    private domSanitizer: DomSanitizer,
    private translation: TranslationService,
    private seo: SeoService
  ) {
    this.matIconRegistry.addSvgIcon(
      'whatsapp',
      this.domSanitizer.bypassSecurityTrustResourceUrl('assets/images/icons/whatsapp.svg')
    );
    this.matIconRegistry.addSvgIcon(
      'telegram',
      this.domSanitizer.bypassSecurityTrustResourceUrl('assets/images/icons/telegram.svg')
    );

    this.subscriptions.push(
      this.translation.languageChanged.subscribe(() => {
        this.seo.generateTags();
      })
    );
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
