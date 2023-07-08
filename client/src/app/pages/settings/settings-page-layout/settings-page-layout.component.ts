import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { RouterModule } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { SettingsLayoutComponent } from 'src/app/components/settings-layout/settings-layout.component';
import { AuthService } from 'src/app/core/auth/auth.service';
import { AboutDialogComponent } from '../components/about-dialog/about-dialog.component';

@Component({
  selector: 'app-settings-page-layout',
  templateUrl: './settings-page-layout.component.html',
  styleUrls: ['./settings-page-layout.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule, TranslocoModule, MatIconModule, MatListModule, SettingsLayoutComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SettingsPageLayoutComponent {
  constructor(public auth: AuthService, private dialog: MatDialog) {}

  openAboutDialog() {
    this.dialog.open(AboutDialogComponent, { width: '400px', autoFocus: false });
  }
}
