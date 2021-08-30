import { Component } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiOptions } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-home',
  templateUrl: './page-home.component.html',
  styleUrls: ['./page-home.component.scss'],
})
export class PageHomeComponent {
  constructor(private api: ApiService) {}

  reload = (options: ApiOptions) => {
    return this.api.getRecipes(options);
  };
}
