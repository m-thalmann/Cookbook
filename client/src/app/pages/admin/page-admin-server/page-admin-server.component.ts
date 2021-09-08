import { Component, OnInit } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { InputDialogComponent } from 'src/app/components/input-dialog/input-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { ServerConfig } from 'src/app/core/api/ApiInterfaces';
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
      console.error('Error loading server-config:', res.error);
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

      if (res.error.info) {
        error = ': ' + res.error.info;
      }

      this.snackbar.error(this.translation.translate('messages.admin.error_saving_config') + error);
      console.error('Error saving config:', res.error);
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
          label: label,
          type: type,
          default: currentValue,
        },
      })
      .afterClosed()
      .toPromise();

    if (value !== null && value !== undefined) {
      await this.updateValue(path, value);
    }
  }

  getHTMLBullets(amount: number) {
    return '&bull;'.repeat(amount);
  }
}
