import { ErrorHandler, Injectable } from '@angular/core';
import { Logger as LoggerClass } from '../helpers/logger';
import { SnackbarService } from './snackbar.service';
import { HandledError } from '../helpers/handled-error';

const Logger = new LoggerClass('ErrorHandlerService');

@Injectable({
  providedIn: 'root',
})
export class ErrorHandlerService implements ErrorHandler {
  constructor(private snackbar: SnackbarService) {}

  handleError(error: any) {
    if (error instanceof HandledError) {
      return;
    }

    Logger.error('An error occurred:', error);

    this.snackbar.warn({ message: 'An unexpected error occurred!', duration: null });
  }
}

