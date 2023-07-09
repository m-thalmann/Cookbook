import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { TranslocoModule } from '@ngneat/transloco';
import { map } from 'rxjs';
import { ConfigService } from 'src/app/core/services/config.service';
import { ThemeService } from 'src/app/core/services/theme.service';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-about-dialog',
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, MatDialogModule, MatButtonModule],
  templateUrl: './about-dialog.component.html',
  styleUrls: ['./about-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AboutDialogComponent {
  isDarkTheme$ = this.theme.theme$.pipe(map((theme) => theme.scheme === 'dark'));

  constructor(private theme: ThemeService, private config: ConfigService) {}

  get version() {
    return environment.version;
  }

  get apiDocsLink() {
    return this.config.get('apiUrl') + '/docs';
  }
}
