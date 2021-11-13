import { Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialogRef } from '@angular/material/dialog';
import { ApiService } from 'src/app/core/api/api.service';
import { getFormError } from 'src/app/core/forms/Validation';
import { Logger, LoggerColor, SubSink } from 'src/app/core/functions';
import { TranslationService } from 'src/app/core/i18n/translation.service';
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

  private subSink = new SubSink();

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private dialogRef: MatDialogRef<CreateUserDialogComponent>,
    private snackbar: SnackbarService,
    public translation: TranslationService
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
      language: [this.translation.languages ? this.translation.languages[0] : null, [Validators.required]],
      isAdmin: [false],
      verifyEmail: [true],
    });

    if (this.password) {
      this.subSink.push(
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
  get language() {
    return this.createForm?.get('language');
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
      this.verifyEmail?.value,
      this.language?.value.key
    );

    this.saving = false;
    this.createForm.enable();
    this.dialogRef.disableClose = true;

    try {
      if (res.isOK()) {
        this.dialogRef.close(true);

        this.snackbar.info('messages.admin.user_created_successfully');
      } else if (res.isConflict()) {
        this.error = 'messages.users.email_already_taken';
      } else {
        throw new Error(res.error?.errorKey ? `api_error.${res.error.errorKey}` : undefined);
      }
    } catch (e: any) {
      this.error = e.message || 'messages.error_occurred';
      Logger.error('CreateUser', LoggerColor.blue, 'Error creating user:', res.error);
    }
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
