import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { RouterModule } from '@angular/router';
import { AuthService } from 'src/app/core/auth/auth.service';

@Component({
  selector: 'app-settings-page-layout',
  templateUrl: './settings-page-layout.component.html',
  styleUrls: ['./settings-page-layout.component.scss'],
  standalone: true,
  imports: [CommonModule, RouterModule, MatIconModule, MatListModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SettingsPageLayoutComponent {
  constructor(public auth: AuthService) {}
}
