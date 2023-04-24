import { ErrorHandler, Injectable, NgZone } from '@angular/core';
import { TranslocoService } from '@ngneat/transloco';
import { HandledError } from '../helpers/handled-error';
import { Logger as LoggerClass } from '../helpers/logger';
import { SnackbarService } from './snackbar.service';

const Logger = new LoggerClass('ErrorHandlerService');

@Injectable({
  providedIn: 'root',
})
export class ErrorHandlerService implements ErrorHandler {
  constructor(private snackbar: SnackbarService, private zone: NgZone, private transloco: TranslocoService) {}

  handleError(error: any) {
    if (error instanceof HandledError) {
      return;
    }

    Logger.error('An error occurred:', error);

    this.zone.run(() => {
      this.snackbar.warn({ message: this.transloco.translate('messages.errors.unexpectedError'), duration: null });
    });
  }
}

