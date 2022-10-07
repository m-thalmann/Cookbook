import { ChangeDetectionStrategy, Component } from '@angular/core';
import { AuthService } from 'src/app/core/auth/auth.service';

@Component({
  selector: 'app-layout-default-navbar',
  templateUrl: './layout-default-navbar.component.html',
  styleUrls: ['./layout-default-navbar.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutDefaultNavbarComponent {
  constructor(public auth: AuthService) {}
}

