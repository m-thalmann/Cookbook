import { HttpClient } from '@angular/common/http';
import { EventEmitter, Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { Logger, LoggerColor } from '../functions';
import StorageNames from '../StorageNames';

const TRANSLATIONS_PATH = 'assets/i18n';
const DEFAULT_LANGUAGE = 'en';

export type TranslationObject = { key: string; replacements?: { [key: string]: string } };

@Injectable({
  providedIn: 'root',
})
export class TranslationService {
  private defaultLanguage: string | null = null;

  private translations: { [key: string]: any } = {};
  private currentLanguage: string | null = null;

  private availableLanguages: { key: string; name: string; flagCode: string }[] | null = null;

  readonly languageChanged = new EventEmitter<string>();
  readonly languageSaved = new EventEmitter<string | null>();

  private _loading = false;

  constructor(private http: HttpClient) {
    this.loadAvailableLanguages();
  }

  /**
   * Switches language & saves it for the user
   *
   * @param language The language to switch to or null if the default one should be used
   */
  async use(language: string | null) {
    if (this.loading) return false;

    this._loading = true;

    let languagesList = [language, this.navigatorLanguage, this.defaultLanguage, DEFAULT_LANGUAGE];
    languagesList = languagesList.filter((lang, index) => {
      return lang && languagesList.indexOf(lang) === index;
    });

    while (languagesList.length > 0) {
      let _language = languagesList.shift();

      if (_language === this.currentLanguage) {
        this._loading = false;
        return true;
      }

      if (await this.load(_language)) {
        const isInit = this.currentLanguage === null;

        this.currentLanguage = _language;

        if (language && language === _language) {
          if (!isInit) {
            this.saveLanguage(_language);
          }
        }

        if (!isInit) {
          this.languageChanged.emit(_language);
        }

        this._loading = false;
        return true;
      }
    }

    this._loading = false;

    Logger.error('TranslationService', LoggerColor.orange, 'No translation file could be loaded!');

    return false;
  }

  /**
   * Initializes the translation with the stored language
   * or the default one if none set
   *
   * @param defaultLanguage The default language from the config
   */
  init(defaultLanguage: string | null) {
    this.defaultLanguage = defaultLanguage;

    return this.use(localStorage.getItem(StorageNames.Language) || null);
  }

  /**
   * Translates a key by searching it in the translation file
   *
   * @param key The key to search for in the translation file (nested translations using '.')
   * @param replacements Replace each found {{key}} (of the object) in the translation with the value
   *
   * @returns The translated value or the key if the translation was not found
   */
  translate(key: string, replacements?: { [key: string]: string }) {
    if (!this.currentLanguage) return key;

    let element = this.translations[this.currentLanguage];

    let keys = key.toLowerCase().split('.');
    let _key = '';

    do {
      _key += keys.shift();

      if (element[_key] && (typeof element[_key] === 'object' || keys.length === 0)) {
        element = element[_key];
        _key = '';
      } else if (!keys.length) {
        element = undefined;
      } else {
        _key += '.';
      }
    } while (keys.length > 0);

    if (typeof element === 'object') {
      element = null;
    }

    if (
      !element &&
      (!environment.production || (window as any).debug || localStorage.getItem(StorageNames.Debug) === 'true')
    ) {
      Logger.warn(
        'TranslationService',
        LoggerColor.orange,
        `Translation not found for key: '${key}' (Language: '${this.currentLanguage}')`
      );
    }

    let value: string = element || key;

    if (replacements) {
      Object.keys(replacements).forEach((replacement) => {
        const regex = new RegExp('\\{\\{' + replacement + '\\}\\}', 'gm');

        value = value.replace(regex, replacements[replacement]);
      });
    }

    return value;
  }

  /**
   * The currently applied language
   */
  get language() {
    return this.currentLanguage;
  }

  /**
   * Whether the translation is currently loading
   */
  get loading() {
    return this._loading;
  }

  /**
   * Loads a translation-file
   *
   * @param language The language code to search for
   *
   * @returns Whether the file could be loaded or not
   */
  private async load(language: string) {
    if (typeof this.translations[language] !== 'undefined') return true;

    try {
      this.translations[language] = await this.http.get<{}>(`${TRANSLATIONS_PATH}/${language}.json`).toPromise();
    } catch (e: any) {
      Logger.error(
        'TranslationService',
        LoggerColor.orange,
        `Error loading translation file (${language}):`,
        e.message
      );
      return false;
    }

    return true;
  }

  /**
   * Loads the _languages.json file, containing all available languages
   */
  private async loadAvailableLanguages() {
    try {
      this.availableLanguages = await this.http.get<any>(`${TRANSLATIONS_PATH}/_languages.json`).toPromise();
    } catch (e: any) {
      Logger.error('TranslationService', LoggerColor.orange, `Error loading available-translations-file:`, e.message);
    }
  }

  /**
   * Saves a selected language for the current user
   *
   * @param language The language to save for the user
   */
  private saveLanguage(language: string | null) {
    if (language) {
      localStorage.setItem(StorageNames.Language, language);
    } else {
      localStorage.removeItem(StorageNames.Language);
    }

    this.languageSaved.emit(language);
  }

  /**
   * The browsers language
   */
  get navigatorLanguage() {
    let ret = navigator.language || (navigator as any)['userLanguage'];

    if (!ret) {
      return null;
    }

    return ret.split('-')[0];
  }

  /**
   * All available languages
   *
   * @see _languages.json
   */
  get languages() {
    return this.availableLanguages;
  }

  /**
   * Searches for the languages information in the availableLanguages
   *
   * @param language The language code to search
   * @returns The languages information of null if not found
   */
  getLanguageInformation(language: string) {
    if (this.availableLanguages) {
      for (let _language of this.availableLanguages) {
        if (_language.key === language) {
          return _language;
        }
      }
    }

    return null;
  }
}
