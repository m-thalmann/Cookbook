import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';

@Component({
  selector: 'app-layout-add-action-fab',
  templateUrl: './layout-add-action-fab.component.html',
  styleUrls: ['./layout-add-action-fab.component.scss'],
  standalone: true,
  imports: [MatButtonModule, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutAddActionFabComponent {
  isOpen = false;
}
