import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ThemeService } from 'src/app/core/services/theme.service';

@Component({
  selector: 'app-general-settings-page',
  templateUrl: './general-settings-page.component.html',
  styleUrls: ['./general-settings-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class GeneralSettingsPageComponent {
  constructor(public themeService: ThemeService) {}

  get themes() {
    return ThemeService.themes;
  }
}

