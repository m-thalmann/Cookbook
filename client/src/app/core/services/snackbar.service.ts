import { Injectable } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';

@Injectable({
  providedIn: 'root',
})
export class SnackbarService {
  constructor(private snackBar: MatSnackBar) {}

  private open({
    message,
    duration,
    warn = false,
    actionName = 'OK',
  }: {
    message: string;
    duration?: number;
    warn?: boolean;
    actionName?: string;
  }) {
    this.snackBar.open(message, actionName, {
      panelClass: warn ? 'action-warn' : undefined,
      duration,
    });
  }

  info(message: string, duration: number | undefined = 5000) {
    this.open({ message, duration, warn: false });
  }

  warn(message: string, duration: number | undefined = 10000) {
    this.open({ message, duration, warn: true });
  }

  error(message: string, duration: number | undefined = undefined) {
    this.open({ message, duration, warn: true });
  }
}

