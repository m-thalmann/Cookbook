import { Component, OnInit } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { ServerConfig } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-admin-server',
  templateUrl: './page-admin-server.component.html',
  styleUrls: ['./page-admin-server.component.scss'],
})
export class PageAdminServerComponent implements OnInit {
  serverConfig: ServerConfig | null = null;
  error = false;

  constructor(private api: ApiService) {}

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
}
