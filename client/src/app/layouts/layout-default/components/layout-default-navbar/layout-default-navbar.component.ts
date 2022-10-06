import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-layout-default-navbar',
  templateUrl: './layout-default-navbar.component.html',
  styleUrls: ['./layout-default-navbar.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutDefaultNavbarComponent {
  constructor() {}
}

