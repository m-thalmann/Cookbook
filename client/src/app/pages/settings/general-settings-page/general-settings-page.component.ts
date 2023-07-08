import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { MatListModule } from '@angular/material/list';
import { LangDefinition, TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject } from 'rxjs';
import { LanguageService } from 'src/app/core/language/language.service';
import { ThemeConfig, ThemeService } from 'src/app/core/services/theme.service';
import { SettingsSectionComponent } from '../../../components/settings-section/settings-section.component';

@Component({
  selector: 'app-general-settings-page',
  templateUrl: './general-settings-page.component.html',
  styleUrls: ['./general-settings-page.component.scss'],
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, MatListModule, SettingsSectionComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class GeneralSettingsPageComponent {
  languageLoading$ = new BehaviorSubject(false);

  constructor(
    public themeService: ThemeService,
    private transloco: TranslocoService,
    private language: LanguageService
  ) {}

  get languages() {
    return this.transloco.getAvailableLangs().map((lang) => {
      if (typeof lang !== 'string') {
        return lang;
      }

      return {
        id: lang,
        label: lang,
      };
    });
  }

  get activeLanguage() {
    return this.transloco.getActiveLang();
  }

  get themes() {
    return ThemeService.themes;
  }

  async setLanguage(language: string) {
    this.languageLoading$.next(true);

    await this.language.selectLanguage(language);

    this.languageLoading$.next(false);
  }

  trackByLanguage(index: number, language: LangDefinition) {
    return language.id;
  }

  trackByTheme(index: number, theme: ThemeConfig) {
    return theme.key;
  }
}
