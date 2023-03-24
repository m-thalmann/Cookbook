import { Injectable, OnDestroy } from '@angular/core';
import {
  BehaviorSubject,
  combineLatest,
  distinctUntilChanged,
  fromEvent,
  map,
  shareReplay,
  startWith,
  Subscription,
} from 'rxjs';
import { StorageService } from './storage.service';

const THEME_KEY = 'THEME';

export interface ThemeConfig {
  key: string;
  scheme: 'dark' | 'light';
  primaryColor: string;
  accentColor: string;
}

const DARK_SCHEME_MEDIA_QUERY = '(prefers-color-scheme: dark)';

@Injectable({
  providedIn: 'root',
})
export class ThemeService implements OnDestroy {
  static readonly themes: ThemeConfig[] = [
    {
      key: 'dark',
      scheme: 'dark',
      primaryColor: '#1976D2',
      accentColor: '#FF5722',
    },
    {
      key: 'light',
      scheme: 'light',
      primaryColor: '#1976D2',
      accentColor: '#FF5722',
    },
    {
      key: 'dark-light-yellow-lime',
      scheme: 'dark',
      primaryColor: '#64DD17',
      accentColor: '#EEFF41',
    },
    {
      key: 'light-purple-pink',
      scheme: 'light',
      primaryColor: '#7B1FA2',
      accentColor: '#C2185B',
    },
  ];

  static readonly defaultDarkTheme = ThemeService.themes[0];
  static readonly defaultLightTheme = ThemeService.themes[1];

  private subSink = new Subscription();

  private _selectedTheme$ = new BehaviorSubject<ThemeConfig | null>(null);
  selectedTheme$ = this._selectedTheme$.pipe(distinctUntilChanged());

  private navigatorTheme$ = fromEvent<MediaQueryList>(window.matchMedia(DARK_SCHEME_MEDIA_QUERY), 'change').pipe(
    startWith(window.matchMedia(DARK_SCHEME_MEDIA_QUERY)),
    map((event) => (event.matches ? ThemeService.defaultDarkTheme : ThemeService.defaultLightTheme)),
    startWith(ThemeService.defaultDarkTheme),
    shareReplay(1)
  );

  theme$ = combineLatest([this.selectedTheme$, this.navigatorTheme$]).pipe(
    map(([selectedTheme, navigatorTheme]) => selectedTheme ?? navigatorTheme),
    distinctUntilChanged()
  );

  constructor(private storage: StorageService) {
    this.subSink.add(
      this.theme$.subscribe((theme) => {
        document.documentElement.classList.remove(...ThemeService.themes.map((theme) => this.getThemeClass(theme)));

        if (theme !== null) {
          document.documentElement.classList.add(this.getThemeClass(theme));
        }
      })
    );
  }

  setTheme(theme: ThemeConfig | null) {
    this._selectedTheme$.next(theme);

    if (theme === null) {
      this.storage.local.remove(THEME_KEY);
    } else {
      this.storage.local.set(THEME_KEY, theme.key);
    }
  }

  private getThemeFromStorage() {
    return ThemeService.themes.find((theme) => theme.key === this.storage.local.get(THEME_KEY)) ?? null;
  }

  load() {
    const theme = this.getThemeFromStorage();
    this.setTheme(theme);
  }

  private getThemeClass(theme: ThemeConfig): string;
  private getThemeClass(theme: null): null;
  private getThemeClass(theme: ThemeConfig | null) {
    if (!theme) {
      return null;
    }

    return `theme-${theme.key}`;
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}

