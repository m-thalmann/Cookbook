import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { TranslationService } from '../i18n/translation.service';

@Injectable({
  providedIn: 'root',
})
export class SnackbarService {
  constructor(private snackBar: MatSnackBar, private translation: TranslationService) {}

  /**
   * Opens a snackbar
   *
   * @param message The message (or message-key if translate is true)
   * @param duration The duration to show the snackbar (or undefined if infinite)
   * @param translate Whether to translate the message
   * @param warn Whether to show the action-button in the warn-color
   */
  private open(message: string, duration: number | undefined, translate = true, warn = false) {
    if (translate) {
      message = this.translation.translate(message);
    }

    this.snackBar.open(message, this.translation.translate('ok'), {
      panelClass: warn ? 'action-warn' : undefined,
      duration: duration,
    });
  }

  /**
   * Opens an info snackbar
   *
   * @param message The message (or message-key if translate is true)
   * @param duration The duration to show the snackbar (or undefined if infinite)
   * @param translate Whether to translate the message
   */
  info(message: string, duration: number | undefined = 5000, translate = true) {
    this.open(message, duration, translate, false);
  }

  /**
   * Opens a warn snackbar
   *
   * @param message The message (or message-key if translate is true)
   * @param duration The duration to show the snackbar (or undefined if infinite)
   * @param translate Whether to translate the message
   */
  warn(message: string, duration: number | undefined = 10000, translate = true) {
    this.open(message, duration, translate, true);
  }

  /**
   * Opens an error snackbar
   *
   * @param message The message (or message-key if translate is true)
   * @param duration The duration to show the snackbar (or undefined if infinite)
   * @param translate Whether to translate the message
   */
  error(message: string, duration: number | undefined = undefined, translate = true) {
    this.open(message, duration, translate, true);
  }
}
