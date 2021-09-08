import { Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialogRef } from '@angular/material/dialog';
import { Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { getFormError } from 'src/app/core/forms/Validation';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'cb-create-user-dialog',
  templateUrl: './create-user-dialog.component.html',
  styleUrls: ['./create-user-dialog.component.scss'],
})
export class CreateUserDialogComponent implements OnDestroy {
  createForm: FormGroup;

  saving = false;
  error: string | null = null;

  private subscriptions: Subscription[] = [];

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private dialogRef: MatDialogRef<CreateUserDialogComponent>,
    private snackbar: SnackbarService
  ) {
    this.createForm = this.fb.group({
      email: ['', [Validators.required, Validators.email, Validators.maxLength(100)]],
      name: ['', [Validators.required, Validators.maxLength(20)]],
      password: ['', Validators.required],
      passwordConfirm: [
        '',
        [
          Validators.required,
          () => {
            if (this.password?.value !== this.passwordConfirm?.value) {
              return { passwordsMismatch: true };
            }
            return null;
          },
        ],
      ],
      isAdmin: [false],
      verifyEmail: [true],
    });

    if (this.password) {
      this.subscriptions.push(
        this.password.valueChanges.subscribe(() => {
          if (this.passwordConfirm) {
            this.passwordConfirm.updateValueAndValidity();
          }
        })
      );
    }
  }

  get email() {
    return this.createForm?.get('email');
  }
  get name() {
    return this.createForm?.get('name');
  }
  get password() {
    return this.createForm?.get('password');
  }
  get passwordConfirm() {
    return this.createForm?.get('passwordConfirm');
  }
  get isAdmin() {
    return this.createForm?.get('isAdmin');
  }
  get verifyEmail() {
    return this.createForm?.get('verifyEmail');
  }

  getFormError(key: string) {
    return getFormError(this.createForm.get(key));
  }

  async save() {
    this.createForm.markAllAsTouched();

    if (this.createForm.invalid) {
      return;
    }

    this.saving = true;
    this.createForm.disable();
    this.error = null;
    this.dialogRef.disableClose = true;

    let res = await this.api.admin.createUser(
      this.email?.value,
      this.password?.value,
      this.name?.value,
      this.isAdmin?.value,
      this.verifyEmail?.value
    );

    this.saving = false;
    this.createForm.enable();
    this.dialogRef.disableClose = true;

    try {
      if (res.isOK()) {
        this.dialogRef.close(true);

        this.snackbar.info('message.admin.user_created_successfully');
      } else if (res.isConflict()) {
        throw new Error('messages.users.email_already_taken');
      } else {
        throw new Error(res.error?.info || undefined);
      }
    } catch (e: any) {
      this.error = e.message || 'messages.error_occurred';
      console.error('Error creating user:', res.error);
    }
  }

  ngOnDestroy() {
    this.subscriptions.forEach((subscription) => subscription.unsubscribe());
  }
}
