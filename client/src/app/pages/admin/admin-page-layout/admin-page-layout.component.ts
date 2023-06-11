import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { RouterModule } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { SettingsLayoutComponent } from 'src/app/components/settings-layout/settings-layout.component';

@Component({
  selector: 'app-admin-page-layout',
  standalone: true,
  imports: [CommonModule, RouterModule, TranslocoModule, MatIconModule, MatListModule, SettingsLayoutComponent],
  templateUrl: './admin-page-layout.component.html',
  styleUrls: ['./admin-page-layout.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminPageLayoutComponent {}

