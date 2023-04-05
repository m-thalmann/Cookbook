import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { ThemeService } from 'src/app/core/services/theme.service';
import { SettingsSectionComponent } from '../components/settings-section/settings-section.component';

@Component({
  selector: 'app-general-settings-page',
  templateUrl: './general-settings-page.component.html',
  styleUrls: ['./general-settings-page.component.scss'],
  standalone: true,
  imports: [CommonModule, MatIconModule, SettingsSectionComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class GeneralSettingsPageComponent {
  constructor(public themeService: ThemeService) {}

  get themes() {
    return ThemeService.themes;
  }
}
