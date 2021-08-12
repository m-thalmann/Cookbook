import { Component } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ActivatedRoute } from '@angular/router';
import { ApiService } from 'src/app/core/api/api.service';
import { Options } from 'src/app/core/api/ApiInterfaces';

@Component({
  selector: 'cb-page-home',
  templateUrl: './page-home.component.html',
  styleUrls: ['./page-home.component.scss'],
})
export class PageHomeComponent {
  results: number | null = null;

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

  reload = async (options: Options) => {
    let res = await this.api.getRecipes(options);

    if (res.isOK() && res.value) {
      this.results = res.value.total_items;
    } else {
      this.results = null;

      console.error('Error loading recipes:', res.error);
    }

    return res;
  };
}
