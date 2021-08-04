import { Component, OnInit } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from 'src/app/core/api/api.service';
import { Pagination, Recipe } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-home',
  templateUrl: './page-home.component.html',
  styleUrls: ['./page-home.component.scss'],
})
export class PageHomeComponent implements OnInit {
  loading = false;
  error = false;
  private _page = 0;

  recipes: Pagination<Recipe> | null = null;

  constructor(private route: ActivatedRoute, private snackBar: MatSnackBar, private api: ApiService) {
    this.route.queryParams.subscribe((params) => {
      if (params.reason) {
        let reason: string;

        switch (params.reason) {
          case 'logout':
            reason = 'Successfully logged out!';
            break;
          case 'badToken':
          case 'unauthorized':
            reason = 'You have to log back in!';
            break;
          default:
            reason = 'You have been logged out';
        }

        this.snackBar.open(reason, 'OK', {
          duration: 5000,
        });
      }
    });
  }

  ngOnInit() {
    this.reload();
  }

  get page() {
    return this._page;
  }

  set page(page: number) {
    this._page = page;
    this.reload();
  }

  async reload() {
    this.loading = true;
    this.error = false;

    let recipes = await this.api.getRecipes(this.page);

    if (recipes.isOK()) {
      this.recipes = recipes.value;
    } else {
      this.error = true;

      console.error('Error loading recipes:', recipes.error);
    }

    this.loading = false;
  }
}
