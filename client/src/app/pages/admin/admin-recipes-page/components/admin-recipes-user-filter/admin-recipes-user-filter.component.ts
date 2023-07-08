import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoModule } from '@ngneat/transloco';
import { SearchUserDialogComponent } from 'src/app/components/dialogs/search-user-dialog/search-user-dialog.component';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { User } from 'src/app/core/models/user';

@Component({
  selector: 'app-admin-recipes-user-filter',
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, MatProgressSpinnerModule],
  templateUrl: './admin-recipes-user-filter.component.html',
  styleUrls: ['./admin-recipes-user-filter.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AdminRecipesUserFilterComponent {
  @Input() user: User | null = null;
  @Output() userChange = new EventEmitter<User | null>();

  @Input()
  @CoerceBooleanProperty()
  disabled: any = false;

  @Input()
  @CoerceBooleanProperty()
  loading: any = false;

  constructor(private dialog: MatDialog) {}

  doRemoveSearchUser() {
    this.userChange.emit(null);
  }

  async openSearchUserDialog() {
    const user: User | null | undefined = await toPromise(
      this.dialog
        .open(SearchUserDialogComponent, {
          data: {
            selectIcon: 'filter_alt',
          },
          width: '350px',
        })
        .afterClosed()
    );

    if (!user) {
      return;
    }

    this.user = user;
    this.userChange.emit(user);
  }
}
