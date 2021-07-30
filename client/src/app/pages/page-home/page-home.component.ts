import { Component, OnInit } from '@angular/core';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'cb-page-home',
  templateUrl: './page-home.component.html',
  styleUrls: ['./page-home.component.scss'],
})
export class PageHomeComponent implements OnInit {
  constructor(private route: ActivatedRoute, private snackBar: MatSnackBar) {
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

  ngOnInit(): void {}
}
