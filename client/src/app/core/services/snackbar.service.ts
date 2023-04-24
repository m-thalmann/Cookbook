import { ComponentType } from '@angular/cdk/portal';
import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { TranslocoService } from '@ngneat/transloco';
import { take } from 'rxjs';

interface SnackbarOptions {
  message: string;
  duration?: number | null;
  actionName?: string;
}

@Injectable({
  providedIn: 'root',
})
export class SnackbarService {
  constructor(private snackBar: MatSnackBar, private transloco: TranslocoService) {}

  private open(
    message: string,
    duration: number | null,
    { warn = false, actionName }: { warn?: boolean; actionName?: string }
  ) {
    if (actionName === undefined) {
      actionName = this.transloco.translate('actions.ok');
    }

    const options = {
      panelClass: warn ? 'action-warn' : undefined,
      duration: duration === null ? undefined : duration,
    };

    const snackbar = this.snackBar.open(message, actionName, options);

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

  info({ message, duration = 5000, actionName }: SnackbarOptions) {
    return this.open(message, duration, { actionName, warn: false });
  }

  warn({ message, duration = 10000, actionName }: SnackbarOptions) {
    return this.open(message, duration, { actionName, warn: true });
  }
}
