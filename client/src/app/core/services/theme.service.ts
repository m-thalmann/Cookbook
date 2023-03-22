import { Injectable } from '@angular/core';
import { BehaviorSubject, distinctUntilChanged } from 'rxjs';
import { StorageService } from './storage.service';

const THEME_KEY = 'THEME';

const THEMES = ['dark', 'light'] as const;

type Theme = typeof THEMES[number] | null;

@Injectable({
  providedIn: 'root',
})
export class ThemeService {
  private _selectedTheme$ = new BehaviorSubject<Theme>(null);
  selectedTheme$ = this._selectedTheme$.pipe(distinctUntilChanged());

  constructor(private storage: StorageService) {}

  setTheme(theme: Theme) {
    this._selectedTheme$.next(theme);

    document.documentElement.classList.remove(...THEMES.map((theme) => this.getThemeClass(theme)));

    if (theme === null) {
      this.storage.local.remove(THEME_KEY);
    } else {
      this.storage.local.set(THEME_KEY, theme);

      document.documentElement.classList.add(this.getThemeClass(theme));
    }
  }

  toggleTheme() {
    const theme = this._selectedTheme$.value;

    const themes = [null, ...THEMES];

    const index = themes.indexOf(theme);

    let nextIndex = 0;

    if (index !== -1) {
      nextIndex = (index + 1) % themes.length;
    }

    this.setTheme(themes[nextIndex]);
  }

  private getThemeFromStorage() {
    return this.storage.local.get(THEME_KEY) as Theme;
  }

  load() {
    const theme = this.getThemeFromStorage();
    this.setTheme(theme);
  }

  private getThemeClass(theme: Theme): string;
  private getThemeClass(theme: null): null;
  private getThemeClass(theme: Theme) {
    if (!theme) {
      return null;
    }

    return `theme-${theme}`;
  }
}

