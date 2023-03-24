import { ChangeDetectionStrategy, Component } from '@angular/core';
import { AuthService } from 'src/app/core/auth/auth.service';

@Component({
  selector: 'app-settings-page-layout',
  templateUrl: './settings-page-layout.component.html',
  styleUrls: ['./settings-page-layout.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SettingsPageLayoutComponent {
  constructor(public auth: AuthService) {}
}

