import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-layout-auth',
  templateUrl: './layout-auth.component.html',
  styleUrls: ['./layout-auth.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutAuthComponent {
  constructor() {}
}

