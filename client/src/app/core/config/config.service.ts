import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';

const URL = 'assets/config.json';

@Injectable({
  providedIn: 'root',
})
export class ConfigService {
  private data = {};

  constructor(private http: HttpClient, private snackBar: MatSnackBar) {}

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
    } catch (e) {
      console.error('Could not load config! Fallback to default values.', e.message);
      this.snackBar.open('Could not load config-file. Did you create it?', 'OK', {
        panelClass: 'action-warn',
      });
    }
  }
}
