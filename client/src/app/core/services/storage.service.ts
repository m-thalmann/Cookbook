import { Injectable } from '@angular/core';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';

const Logger = new LoggerClass('StorageService');

const PREFIX = 'CB_';

@Injectable({
  providedIn: 'root',
})
export class StorageService {
  constructor() {}

  public get local() {
    return this.operations(localStorage);
  }

  public get session() {
    return this.operations(sessionStorage);
  }

  private operations(storage: Storage) {
    return {
      get: <T>(key: string, defaultValue: T | null = null) => this.get<T>(storage, key, defaultValue),
      set: (key: string, value: any) => this.set(storage, key, value),
      remove: (key: string) => this.remove(storage, key),
    };
  }

  private generateKey(key: string) {
    return PREFIX + key;
  }

  private get<T>(storage: Storage, key: string, defaultValue: T | null = null): T | null {
    const value = storage.getItem(this.generateKey(key));

    if (value !== null) {
      try {
        return JSON.parse(value);
      } catch (e) {
        Logger.error(`Error parsing value for key '${key}': ${e}`);
      }
    }

    return defaultValue;
  }

  private set(storage: Storage, key: string, value: any) {
    storage.setItem(this.generateKey(key), JSON.stringify(value));
  }

  private remove(storage: Storage, key: string) {
    storage.removeItem(this.generateKey(key));
  }
}

