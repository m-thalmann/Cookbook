import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { BehaviorSubject } from 'rxjs';
import { SearchUserDialogComponent } from 'src/app/components/dialogs/search-user-dialog/search-user-dialog.component';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { User } from 'src/app/core/models/user';

@Component({
  selector: 'app-edit-recipe-user-select-form-control',
  standalone: true,
  imports: [CommonModule, MatIconModule],
  templateUrl: './edit-recipe-user-select-form-control.component.html',
  styleUrls: ['./edit-recipe-user-select-form-control.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: EditRecipeUserSelectFormControlComponent,
      multi: true,
    },
  ],
})
export class EditRecipeUserSelectFormControlComponent implements ControlValueAccessor {
  @Input() error: string | null = null;

  user$ = new BehaviorSubject<User | null>(null);
  disabled$ = new BehaviorSubject<boolean>(false);

  private onChange = (_: User | null) => {};
  private onTouched = () => {};

  constructor(private dialog: MatDialog) {}

  writeValue(value: User | null) {
    this.user$.next(value);
  }

  registerOnChange(fn: (value: User | null) => void) {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void) {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean) {
    this.disabled$.next(isDisabled);
  }

  async openSearchUserDialog() {
    const user: User | null | undefined = await toPromise(
      this.dialog
        .open(SearchUserDialogComponent, {
          width: '350px',
        })
        .afterClosed()
    );

    if (!user) {
      return;
    }

    this.user$.next(user);

    this.onChange(user);
    this.onTouched();
  }
}
