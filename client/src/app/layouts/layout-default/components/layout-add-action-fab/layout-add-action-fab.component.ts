import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { TranslocoModule } from '@ngneat/transloco';

@Component({
  selector: 'app-layout-add-action-fab',
  templateUrl: './layout-add-action-fab.component.html',
  styleUrls: ['./layout-add-action-fab.component.scss'],
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatButtonModule, MatIconModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class LayoutAddActionFabComponent {
  @Input()
  set disabled(disabled: boolean) {
    this._disabled = disabled;

    if (disabled) {
      this.isOpen = false;
    }
  }
  get disabled() {
    return this._disabled;
  }

  private _disabled = false;

  isOpen = false;
}
