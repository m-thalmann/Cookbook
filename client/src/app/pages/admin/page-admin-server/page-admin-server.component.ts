import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { ConfirmDialogComponent } from 'src/app/components/confirm-dialog/confirm-dialog.component';
import { InputDialogComponent } from 'src/app/components/input-dialog/input-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { ServerConfig } from 'src/app/core/api/ApiInterfaces';
import { Logger, LoggerColor } from 'src/app/core/functions';
import { TranslationService } from 'src/app/core/i18n/translation.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'cb-page-admin-server',
  templateUrl: './page-admin-server.component.html',
  styleUrls: ['./page-admin-server.component.scss'],
})
export class PageAdminServerComponent implements OnInit {
  serverConfig: ServerConfig | null = null;

  error = false;
  loading = false;

  sendingTestEmail = false;

  constructor(
    private api: ApiService,
    private dialog: MatDialog,
    private snackbar: SnackbarService,
    private translation: TranslationService
  ) {}

  ngOnInit() {
    this.loadServerConfig();
  }

  async loadServerConfig() {
    this.error = false;

    let res = await this.api.admin.getServerConfig();

    if (res.isOK()) {
      this.serverConfig = res.value;
    } else {
      Logger.error('PageAdminServer', LoggerColor.blue, 'Error loading server-config:', res.error);
      this.error = true;
    }
  }

  getServerConfig(key: string) {
    return (this.serverConfig as any)[key];
  }

  async updateValue(path: string, value: any) {
    this.loading = true;

    let res = await this.api.admin.updateServerConfig(path, value);

    if (res.isOK()) {
      await this.loadServerConfig();

      this.snackbar.info('messages.admin.config_edited_successfully');
    } else {
      let error = '';

      if (res.error?.errorKey) {
        error = ' ' + this.translation.translate(`api_error.${res.error.errorKey}`);
      }

      this.snackbar.error(this.translation.translate('messages.admin.error_saving_config') + error);
      Logger.error('PageAdminServer', LoggerColor.blue, 'Error saving config:', res.error);
    }

    this.loading = false;
  }

  updateValueFromEvent(path: string, event: Event) {
    return this.updateValue(path, (event.target as HTMLInputElement).value);
  }

  async openEditDialog(path: string, type: 'text' | 'password' | 'number', label: string | null) {
    if (this.loading) return;

    let currentValue = (this.serverConfig as any)[path];

    let value = await this.dialog
      .open(InputDialogComponent, {
        data: {
          translate: true,
          title: 'pages.admin.server.edit_config',
          label,
          type,
          default: currentValue,
        },
      })
      .afterClosed()
      .toPromise();

    if (value !== null && value !== undefined) {
      await this.updateValue(path, value);
    }
  }

  async sendTestEmail() {
    if (this.loading) return;

    let email = await this.dialog
      .open(InputDialogComponent, {
        data: {
          translate: true,
          title: 'pages.admin.server.mail.send_test_email',
          label: 'email',
          type: 'email',
          btnConfirm: 'pages.admin.server.mail.send',
        },
      })
      .afterClosed()
      .toPromise();

    if (!email) return;

    this.loading = true;
    this.sendingTestEmail = true;

    let res = await this.api.admin.sendTestEmail(email);

    if (res.isOK()) {
      this.snackbar.info('messages.admin.test_email_sent_successfully');
    } else {
      this.snackbar.warn(
        `${this.translation.translate('messages.email.error_sending_email')} ${res.error?.details}`,
        10000,
        false
      );
      Logger.error('PageAdminServer', LoggerColor.blue, 'Error sending test-email:', res.error);
    }

    this.loading = false;
    this.sendingTestEmail = false;
  }

  getHTMLBullets(amount: number) {
    return '&bull;'.repeat(amount);
  }

  openInfoDialog(titleKey: string, textKey: string) {
    this.dialog.open(ConfirmDialogComponent, {
      data: {
        onlyOk: true,
        title: titleKey,
        content: textKey,
        translate: true,
      },
      width: '250px',
    });
  }
}
