import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouteHelperService } from 'src/app/core/services/route-helper.service';

@Component({
  selector: 'app-layout-auth',
  templateUrl: './layout-auth.component.html',
  styleUrls: ['./layout-auth.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutAuthComponent {

  constructor(public routeHelper: RouteHelperService) {}
}
