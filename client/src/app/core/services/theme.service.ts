import { Injectable, OnDestroy } from '@angular/core';
import {
  BehaviorSubject,
  combineLatest,
  distinctUntilChanged,
  from,
  fromEvent,
  map,
  Observable,
  startWith,
  Subscription,
} from 'rxjs';
import { StorageService } from './storage.service';

const THEME_KEY = 'THEME';

const THEMES = ['dark', 'light'] as const;

type Theme = typeof THEMES[number] | null;

const DARK_SCHEME_MEDIA_QUERY = '(prefers-color-scheme: dark)';

@Injectable({
  providedIn: 'root',
})
export class ThemeService implements OnDestroy {
  private subSink = new Subscription();

  private _selectedTheme$ = new BehaviorSubject<Theme>(null);
  selectedTheme$ = this._selectedTheme$.pipe(distinctUntilChanged());

  private navigatorTheme$ = fromEvent<MediaQueryList>(window.matchMedia(DARK_SCHEME_MEDIA_QUERY), 'change').pipe(
    map((event) => event.matches),
    startWith(window.matchMedia(DARK_SCHEME_MEDIA_QUERY).matches),
    map((isDark) => (isDark ? 'dark' : 'light') as Theme)
  );

  theme$ = combineLatest([this.selectedTheme$, this.navigatorTheme$]).pipe(
    map(([selectedTheme, navigatorTheme]) => selectedTheme ?? navigatorTheme),
    distinctUntilChanged()
  );

  constructor(private storage: StorageService) {
    this.subSink.add(
      this.theme$.subscribe((theme) => {
        document.documentElement.classList.remove(...THEMES.map((theme) => this.getThemeClass(theme)));

        if (theme !== null) {
          document.documentElement.classList.add(this.getThemeClass(theme));
        }
      })
    );
  }

  setTheme(theme: Theme) {
    this._selectedTheme$.next(theme);

    if (theme === null) {
      this.storage.local.remove(THEME_KEY);
    } else {
      this.storage.local.set(THEME_KEY, theme);
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

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}

