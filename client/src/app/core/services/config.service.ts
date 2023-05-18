import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { Logger as LoggerClass } from '../helpers/logger';
import { toPromise } from '../helpers/to-promise';

const CONFIG_URL = '/assets/config.json';

type ConfigType = { [key: string]: any };

const Logger = new LoggerClass('ConfigService');

@Injectable({
  providedIn: 'root',
})
export class ConfigService {
  private data: ConfigType = {};

  constructor(private http: HttpClient) {}

  get(key: string, defaultValue: any = null): any {
    if (typeof this.data[key] !== 'undefined') {
      return this.data[key];
    }

    return defaultValue;
  }

  async load() {
    try {
      this.data = await toPromise(this.http.get<ConfigType>(CONFIG_URL));
    } catch (e) {
      let error = e;

      if (e instanceof HttpErrorResponse) {
        error = e.statusText;
      }

      Logger.error('Config could not be loaded:', error);
    }
  }
}
