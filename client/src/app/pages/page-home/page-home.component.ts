import { Component } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { Options } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-home',
  templateUrl: './page-home.component.html',
  styleUrls: ['./page-home.component.scss'],
})
export class PageHomeComponent {
  results: number | null = null;

  constructor(private api: ApiService) {}

  reload = (options: Options) => {
    return this.api.getRecipes(options);
  };
}
