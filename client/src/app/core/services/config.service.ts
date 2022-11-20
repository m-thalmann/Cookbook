import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { lastValueFrom } from 'rxjs';
import { Logger as LoggerClass } from '../helpers/logger';

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
      this.data = await lastValueFrom(this.http.get<ConfigType>(CONFIG_URL));
    } catch (e) {
      let error = e;

      if (e instanceof HttpErrorResponse) {
        error = e.statusText;
      }

      Logger.error('Config could not be loaded:', error);
    }
  }
}

