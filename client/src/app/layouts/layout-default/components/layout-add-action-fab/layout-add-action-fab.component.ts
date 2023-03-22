import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-layout-add-action-fab',
  templateUrl: './layout-add-action-fab.component.html',
  styleUrls: ['./layout-add-action-fab.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutAddActionFabComponent {
  isOpen = false;
}

