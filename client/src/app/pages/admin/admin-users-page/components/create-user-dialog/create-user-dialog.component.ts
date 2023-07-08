import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MAT_FORM_FIELD_DEFAULT_OPTIONS, MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, Subscription, distinctUntilChanged } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { CreateUserData } from 'src/app/core/models/user';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Admin');

@Component({
  selector: 'app-create-user-dialog',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    TranslocoModule,
    MatDialogModule,
    MatIconModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatCheckboxModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './create-user-dialog.component.html',
  styleUrls: ['./create-user-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [
    {
      provide: MAT_FORM_FIELD_DEFAULT_OPTIONS,
      useValue: { subscriptSizing: 'dynamic', hideRequiredMarker: true, appearance: 'outline' },
    },
  ],
})
export class CreateUserDialogComponent {
  private subSink = new Subscription();

  saving$ = new BehaviorSubject(false);

  form = this.fb.nonNullable.group({
    name: [<string>'', [Validators.required]],
    email: [<string>'', [Validators.email, Validators.required]],
    password: [<string>'', [Validators.required]],
    is_admin: [false, []],
    is_verified: [false, []],
    send_verification_email: [false, []],
  });

  constructor(
    private dialogRef: MatDialogRef<CreateUserDialogComponent>,
    private fb: FormBuilder,
    private api: ApiService,
    private snackbar: SnackbarService
  ) {
    this.subSink.add(
      this.saving$.pipe(distinctUntilChanged()).subscribe((saving) => {
        this.dialogRef.disableClose = saving;

        if (saving) {
          this.form.disable();
        } else {
          this.form.enable();
        }
      })
    );
  }

  async onSubmit() {
    if (this.saving$.value || this.form.invalid) return;

    this.saving$.next(true);

    const createUserData: CreateUserData = {
      name: this.form.controls.name.value,
      email: this.form.controls.email.value,
      password: this.form.controls.password.value,
      is_admin: this.form.controls.is_admin.value,
      is_verified: this.form.controls.is_verified.value,
      send_verification_email: this.form.controls.send_verification_email.value,
    };

    try {
      await toPromise(this.api.users.create(createUserData));

      this.snackbar.info('messages.userCreated', { translateMessage: true });

      this.dialogRef.close(true);
    } catch (e) {
      this.saving$.next(false);

      if (!(e instanceof HttpErrorResponse && ServerValidationHelper.setValidationErrors(e, this.form))) {
        const errorMessage = this.snackbar.exception(e, {}).message;

        Logger.error('Error creating user:', errorMessage, e);
      }
    }
  }
}
