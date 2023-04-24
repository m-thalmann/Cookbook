import { ChangeDetectionStrategy, Component, OnDestroy } from '@angular/core';
import { MatIconRegistry } from '@angular/material/icon';
import { DomSanitizer } from '@angular/platform-browser';
import { RouterOutlet } from '@angular/router';
import { SwUpdate } from '@angular/service-worker';
import { TranslocoService } from '@ngneat/transloco';
import { IconSnackbarComponent } from './components/snackbar/icon-snackbar/icon-snackbar.component';
import { SnackbarService } from './core/services/snackbar.service';

@Component({
  selector: 'app-root',
  template: `<router-outlet></router-outlet>`,
  styles: [],
  standalone: true,
  imports: [RouterOutlet],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AppComponent implements OnDestroy {
  private updateSubscription = this.swUpdate.versionUpdates.subscribe((event) => {
    if (event.type !== 'VERSION_READY') {
      return;
    }

    this.snackbar.openComponent(
      IconSnackbarComponent,
      {
        message: this.transloco.translate('messages.updateAvailable'),
        icon: 'browser_updated',
        action: {
          name: 'download',
          isIcon: true,
          color: 'primary',
          callback: () => document.location.reload(),
        },
      },
      null
    );
  });

  constructor(
    private matIconRegistry: MatIconRegistry,
    private domSanitizer: DomSanitizer,
    private swUpdate: SwUpdate,
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {
    this.registerIcons();
  }

  ngOnDestroy() {
    this.updateSubscription.unsubscribe();
  }

  private registerIcons() {
    this.matIconRegistry.setDefaultFontSetClass('material-icons-round');

    this.matIconRegistry.addSvgIcon(
      'whatsapp',
      this.domSanitizer.bypassSecurityTrustResourceUrl('assets/images/icons/whatsapp.svg')
    );
    this.matIconRegistry.addSvgIcon(
      'telegram',
      this.domSanitizer.bypassSecurityTrustResourceUrl('assets/images/icons/telegram.svg')
    );
  }
}
