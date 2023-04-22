import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';
import { AuthToken } from 'src/app/core/models/auth-token';

@Component({
  selector: 'app-security-settings-active-token',
  standalone: true,
  imports: [CommonModule, MatButtonModule, MatIconModule, MatTooltipModule],
  templateUrl: './security-settings-active-token.component.html',
  styleUrls: ['./security-settings-active-token.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SecuritySettingsActiveTokenComponent {
  @Input() token!: AuthToken;
  @Input() disabled = false;

  @Output() logout = new EventEmitter<void>();

  expanded = false;
}

