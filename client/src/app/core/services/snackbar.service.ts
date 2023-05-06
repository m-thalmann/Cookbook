import { ComponentType } from '@angular/cdk/portal';
import { HttpErrorResponse } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { TranslocoService } from '@ngneat/transloco';
import { take } from 'rxjs';
import { ApiService } from '../api/api.service';

interface SnackbarOptions {
  duration?: number | null;
  actionName?: string;
  translateMessage?: boolean;
}

@Injectable({
  providedIn: 'root',
})
export class SnackbarService {
  constructor(private snackBar: MatSnackBar, private transloco: TranslocoService) {}

  private open(
    message: string,
    duration: number | null,
    { warn = false, actionName, translateMessage }: { warn?: boolean; actionName?: string; translateMessage?: boolean }
  ) {
    if (actionName === undefined) {
      actionName = this.transloco.translate('actions.ok');
    }

    const options = {
      panelClass: warn ? 'action-warn' : undefined,
      duration: duration === null ? undefined : duration,
    };

    const snackbar = this.snackBar.open(
      translateMessage ? this.transloco.translate(message) : message,
      actionName,
      options
    );

    return {
      action: (action: () => void) => {
        snackbar.onAction().pipe(take(1)).subscribe(action);
      },
    };
  }

  openComponent(component: ComponentType<unknown>, data: any, duration: number | null = null) {
    this.snackBar.openFromComponent(component, {
      data: data,
      duration: duration === null ? undefined : duration,
    });
  }

  info(message: string, { duration = 5000, actionName, translateMessage = false }: SnackbarOptions) {
    return this.open(message, duration, { actionName, warn: false, translateMessage });
  }

  warn(message: string, { duration = 10000, actionName, translateMessage = false }: SnackbarOptions) {
    return this.open(message, duration, { actionName, warn: true, translateMessage });
  }

  exception(
    error: unknown,
    {
      defaultMessage,
      duration = 10000,
      actionName,
      translateMessage = false,
    }: { defaultMessage?: string } & SnackbarOptions
  ) {
    let message: string | undefined = undefined;

    if (error instanceof HttpErrorResponse) {
      let apiErrorMessage = ApiService.getErrorMessage(error);

      if (typeof apiErrorMessage === 'string') {
        message = apiErrorMessage;
      }
    }

    if (!message) {
      if (!defaultMessage) {
        message = this.transloco.translate('messages.errors.errorOccurred')!;
      } else {
        message = translateMessage ? this.transloco.translate(defaultMessage)! : defaultMessage;
      }
    }

    return {
      message: message,
      snackbar: this.warn(message, { duration, actionName, translateMessage: false }),
    };
  }
}
