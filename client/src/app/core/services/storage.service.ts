import { Injectable } from '@angular/core';
import { Observable, filter, fromEvent, map, shareReplay } from 'rxjs';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';

const Logger = new LoggerClass('StorageService');

const PREFIX = 'CB_';

@Injectable({
  providedIn: 'root',
})
export class StorageService {
  constructor() {}

  get<T>(key: string, defaultValue: T | null = null): T | null {
    const value = localStorage.getItem(this.generateKey(key));

    if (value !== null) {
      try {
        return JSON.parse(value);
      } catch (e) {
        Logger.error(`Error parsing value for key '${key}': ${e}`);
      }
    }

    return defaultValue;
  }

  /**
   * Observes the value change of a key in localStorage.
   *
   * __Important:__ The value is only observed if the value is changed in another tab.
   */
  observe<T>(key: string, defaultValue: T | null = null): Observable<T | null> {
    const generatedKey = this.generateKey(key);

    return fromEvent<StorageEvent>(window, 'storage').pipe(
      filter((event) => event.key === generatedKey),
      map((event) => this.parseValue<T>(generatedKey, event.newValue, defaultValue)),
      shareReplay({ bufferSize: 1, refCount: true })
    );
  }

  set(key: string, value: any) {
    localStorage.setItem(this.generateKey(key), JSON.stringify(value));
  }

  remove(key: string) {
    localStorage.removeItem(this.generateKey(key));
  }

  private generateKey(key: string) {
    return PREFIX + key;
  }

  private parseValue<T>(key: string, value: string | null, defaultValue: T | null = null): T | null {
    if (value !== null) {
      try {
        return JSON.parse(value);
      } catch (e) {
        Logger.error(`Error parsing value for key '${key}': ${e}`);
      }
    }

    return defaultValue;
  }
}
