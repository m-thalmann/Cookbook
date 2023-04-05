import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-settings-section',
  templateUrl: './settings-section.component.html',
  styleUrls: ['./settings-section.component.scss'],
  standalone: true,
  imports: [MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SettingsSectionComponent {
  @Input() icon!: string;
  @Input() title!: string;

  @Input() titleColor: 'primary' | 'accent' | 'warn' | null = null;
}
