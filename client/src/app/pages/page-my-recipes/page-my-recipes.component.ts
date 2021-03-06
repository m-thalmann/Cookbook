import { Component } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { ApiOptions } from 'src/app/core/api/ApiInterfaces';
import { UserService } from 'src/app/core/auth/user.service';

@Component({
  selector: 'cb-page-my-recipes',
  templateUrl: './page-my-recipes.component.html',
  styleUrls: ['./page-my-recipes.component.scss'],
})
export class PageMyRecipesComponent {
  constructor(private api: ApiService, private user: UserService) {}

  reload = (options: ApiOptions) => {
    if (!this.user.user) return null;

    return this.api.getRecipesForUser(this.user.user.id, options);
  };
}
