import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { SnackbarService } from '../services/snackbar.service';

const URL = 'assets/config.json';

@Injectable({
  providedIn: 'root',
})
export class ConfigService {
  private data = {};

  constructor(private http: HttpClient, private snackbar: SnackbarService) {}

  /**
   * Get a value from the config
   *
   * @param path the path of the value, separated by "."
   * @param def the default value to return, if the value is not found
   * @returns the found value
   */
  get(path: string, def: any = null): any {
    let val: any = this.data;
    let segments = path.split('.');

    for (let index in segments) {
      let segment = segments[index];

      if (typeof val[segment] === 'undefined') {
        return def;
      }

      val = val[segment];
    }

    return val;
  }

  /**
   * Loads the config file
   */
  async load() {
    try {
      this.data = await this.http.get<{}>(URL).toPromise();
    } catch (e: any) {
      console.error('Could not load config! Fallback to default values.', e.message);
      setTimeout(() => this.snackbar.error('messages.config_loading_error'), 500);
    }
  }
}
