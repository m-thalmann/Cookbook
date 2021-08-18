import { Component } from '@angular/core';
import { UserService } from 'src/app/core/auth/user.service';

@Component({
  selector: 'cb-logout',
  template: ``,
  styleUrls: [],
})
export class LogoutComponent {
  constructor(private user: UserService) {
    this.user.logout('logout');
  }
}
