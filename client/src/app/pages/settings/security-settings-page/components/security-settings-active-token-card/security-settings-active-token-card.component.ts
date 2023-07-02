import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { MatTooltipModule } from '@angular/material/tooltip';
import { TranslocoModule } from '@ngneat/transloco';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { AuthToken } from 'src/app/core/models/auth-token';
import { I18nDatePipe } from 'src/app/core/pipes/i18n-date.pipe';

@Component({
  selector: 'app-security-settings-active-token-card',
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatButtonModule,
    MatIconModule,
    MatTooltipModule,
    SkeletonComponent,
    I18nDatePipe,
  ],
  templateUrl: './security-settings-active-token-card.component.html',
  styleUrls: ['./security-settings-active-token-card.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SecuritySettingsActiveTokenCardComponent {
  @Input() token!: AuthToken | null;

  @Input()
  @CoerceBooleanProperty()
  disabled: any = false;

  @Output() logout = new EventEmitter<void>();

  expanded = false;
}
