import { Component } from '@angular/core';
import { ApiService } from 'src/app/core/api/api.service';
import { UserService } from 'src/app/core/auth/user.service';

@Component({
  selector: 'cb-page-my-recipes',
  templateUrl: './page-my-recipes.component.html',
  styleUrls: ['./page-my-recipes.component.scss'],
})
export class PageMyRecipesComponent {
  results: number | null = null;

  constructor(private api: ApiService, private user: UserService) {}

  reload = async (page: number) => {
    let res = await this.api.getRecipesForUser(this.user.user!.id, page);

    if (res.isOK() && res.value) {
      this.results = res.value.total_items;
    } else {
      this.results = null;
    }

    return res;
  };
}
