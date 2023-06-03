import { coerceBooleanProperty } from '@angular/cdk/coercion';
import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { Cookbook } from 'src/app/core/models/cookbook';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Cookbooks');

@Component({
  selector: 'app-edit-cookbook-details-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    TranslocoModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './edit-cookbook-details-form.component.html',
  styleUrls: ['./edit-cookbook-details-form.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EditCookbookDetailsFormComponent {
  private subSink = new Subscription();

  @Input()
  set cookbook(cookbook: Cookbook | null) {
    this._cookbook = cookbook;

    this.resetForm();
  }

  get cookbook() {
    return this._cookbook;
  }

  private _cookbook: Cookbook | null = null;

  @Input()
  set disabled(disabled: any) {
    const isDisabled = coerceBooleanProperty(disabled);

    if (isDisabled) {
      this.form.disable();
    } else if (!this.saving.value) {
      this.form.enable();
    }

    this._disabled = isDisabled;
  }

  private _disabled = false;

  @Output() saving = new BehaviorSubject<boolean>(false);

  savingError$ = new BehaviorSubject<string | null>(null);

  @Output() saved = new EventEmitter<void>();

  form = this.fb.nonNullable.group({
    name: [<string>'', [Validators.required]],
  });

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private transloco: TranslocoService,
    private snackbar: SnackbarService
  ) {
    this.subSink.add(
      this.saving.subscribe((saving) => {
        if (saving) {
          this.form.disable();
        } else if (!this._disabled) {
          this.form.enable();
        }
      })
    );
  }

  resetForm() {
    if (!this.cookbook) {
      this.form.reset();

      return;
    }

    this.form.patchValue({
      name: this.cookbook.name,
    });
  }

  async onSubmit() {
    if (!this.cookbook || this.form.invalid || this.form.disabled || this.saving.value) {
      return;
    }

    this.savingError$.next(null);
    this.saving.next(true);

    try {
      await toPromise(this.api.cookbooks.update(this.cookbook.id, this.form.controls.name.value));

      this.snackbar.info('messages.cookbookUpdated', { translateMessage: true });

      this.saved.emit();
      this.saving.next(false);
    } catch (e) {
      this.saving.next(false);

      let errorMessage: string | null = this.transloco.translate('messages.errors.unexpectedError');

      if (e instanceof HttpErrorResponse) {
        if (ServerValidationHelper.setValidationErrors(e, this.form)) {
          errorMessage = null;
        } else {
          errorMessage = this.api.getErrorMessage(e);
        }
      }

      if (errorMessage) {
        this.savingError$.next(errorMessage);
      }

      Logger.error('Error updating cookbook:', errorMessage, e);
    }
  }
}

