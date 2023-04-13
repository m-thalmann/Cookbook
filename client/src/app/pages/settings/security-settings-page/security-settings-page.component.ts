import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { SecuritySettingsActiveTokensComponent } from './components/security-settings-active-tokens/security-settings-active-tokens.component';
import { SecuritySettingsEmailVerificationComponent } from './components/security-settings-email-verification/security-settings-email-verification.component';

@Component({
  selector: 'app-security-settings-page',
  templateUrl: './security-settings-page.component.html',
  styleUrls: ['./security-settings-page.component.scss'],
  standalone: true,
  imports: [CommonModule, SecuritySettingsEmailVerificationComponent, SecuritySettingsActiveTokensComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SecuritySettingsPageComponent {}
