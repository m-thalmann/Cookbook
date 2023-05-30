import { coerceBooleanProperty } from '@angular/cdk/coercion';
import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { TranslocoModule } from '@ngneat/transloco';
import { CreateCookbookDialogComponent } from 'src/app/pages/cookbooks/components/create-cookbook-dialog/create-cookbook-dialog.component';

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
  set disabled(disabled: any) {
    this._disabled = coerceBooleanProperty(disabled);

    if (disabled) {
      this.isOpen = false;
    }
  }
  get disabled() {
    return this._disabled;
  }

  private _disabled = false;

  isOpen = false;

  constructor(private dialog: MatDialog) {}

  openCreateCookbookDialog() {
    this.dialog.open(CreateCookbookDialogComponent);
  }
}
