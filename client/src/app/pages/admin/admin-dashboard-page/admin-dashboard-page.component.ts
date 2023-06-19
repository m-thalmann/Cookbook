import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { TranslocoModule } from '@ngneat/transloco';
import { shareReplay, switchMap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';

@Component({
  selector: 'app-admin-dashboard-page',
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, ErrorDisplayComponent, SkeletonComponent, RepeatDirective],
  templateUrl: './admin-dashboard-page.component.html',
  styleUrls: ['./admin-dashboard-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminDashboardPageComponent {
  dashboard$ = this.auth.user$.pipe(
    switchMap(() => this.api.admin.dashboard.get()),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.dashboard$);

  constructor(private api: ApiService, private auth: AuthService) {}

  formatBytes(bytes: number) {
    const sizes = ['b', 'kb', 'mb', 'gb'];

    let value = bytes;
    let size = sizes[0];

    if (bytes > 0) {
      const k = 1024;
      const dm = 2;

      const i = Math.floor(Math.log(bytes) / Math.log(k));

      value = parseFloat((bytes / Math.pow(k, i)).toFixed(dm));
      size = sizes[i];
    }

    return {
      value,
      sizeTranslationKey: `pages.admin.children.dashboard.fileSizes.${size}`,
    };
  }
}

