import { Component, OnInit } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { ServerInformation } from 'src/app/core/api/ApiInterfaces';
import { Logger, LoggerColor } from 'src/app/core/functions';

@Component({
  selector: 'cb-page-admin-home',
  templateUrl: './page-admin-home.component.html',
  styleUrls: ['./page-admin-home.component.scss'],
})
export class PageAdminHomeComponent implements OnInit {
  serverInformation: ServerInformation | null = null;
  loading = false;
  error = false;

  constructor(private api: ApiService) {}

  ngOnInit() {
    this.loadServerInformation();
  }

  async loadServerInformation() {
    this.serverInformation = null;
    this.loading = true;
    this.error = false;

    let res = await this.api.admin.getServerInformation();

    if (res.isOK()) {
      this.serverInformation = res.value;
    } else {
      Logger.error('PageAdminHome', LoggerColor.blue, 'Error loading server information:', res.error);
      this.error = true;
    }

    this.loading = false;
  }
}
