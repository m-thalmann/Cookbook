import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';

@Component({
  selector: 'app-settings-section',
  templateUrl: './settings-section.component.html',
  styleUrls: ['./settings-section.component.scss'],
  standalone: true,
  imports: [CommonModule, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SettingsSectionComponent {
  @Input() icon!: string;
  @Input() title!: string;

  @Input() titleColor: 'primary' | 'accent' | 'warn' | null = null;

  @Input()
  @CoerceBooleanProperty()
  warn: any = false;
}
