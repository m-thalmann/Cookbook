import { Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialog, MatDialogRef } from '@angular/material/dialog';
import { ConfirmDialogComponent } from 'src/app/components/confirm-dialog/confirm-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { UserService } from 'src/app/core/auth/user.service';
import { getFormError } from 'src/app/core/forms/Validation';
import { SubSink } from 'src/app/core/functions';
import { TranslationService } from 'src/app/core/i18n/translation.service';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

@Component({
  selector: 'cb-settings-dialog',
  templateUrl: './settings-dialog.component.html',
  styleUrls: ['./settings-dialog.component.scss'],
})
export class SettingsDialogComponent implements OnDestroy {
  settingsForm: FormGroup;

  private _editEmail = false;
  private _editName = false;
  private _editPassword = false;

  saving = false;
  error: string | null = null;

  private subSink = new SubSink();

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private user: UserService,
    private dialogRef: MatDialogRef<SettingsDialogComponent>,
    private snackbar: SnackbarService,
    private dialog: MatDialog,
    private translation: TranslationService
  ) {
    this.settingsForm = this.fb.group(
      {
        email: [
          { value: this.user.user?.email || '', disabled: true },
          [
            Validators.email,
            () => {
              if (this.editEmail && this.email?.value.trim().length === 0) {
                return { required: true };
              }
              return null;
            },
          ],
        ],
        name: [
          { value: this.user.user?.name || '', disabled: true },
          () => {
            if (this.editName && this.name?.value.trim().length === 0) {
              return { required: true };
            }
            return null;
          },
        ],
        currentPassword: ['', Validators.required],
        password: [
          { value: '123456789', disabled: true },
          () => {
            if (this.editPassword && this.password?.value.trim().length === 0) {
              return { required: true };
            }
            return null;
          },
        ],
        passwordConfirm: [
          '',
          () => {
            if (this.editPassword && this.password?.value != this.passwordConfirm?.value) {
              return { passwordsMismatch: true };
            }
            if (this.editPassword && this.passwordConfirm?.value.trim().length === 0) {
              return { required: true };
            }
            return null;
          },
        ],
      },
      {
        validators: [
          () => {
            if (!this.editPassword && !this.editEmail && !this.editName) {
              return { updateRequired: true };
            }

            return null;
          },
        ],
      }
    );

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
    return this.settingsForm?.get('email');
  }
  get name() {
    return this.settingsForm?.get('name');
  }
  get currentPassword() {
    return this.settingsForm?.get('currentPassword');
  }
  get password() {
    return this.settingsForm?.get('password');
  }
  get passwordConfirm() {
    return this.settingsForm?.get('passwordConfirm');
  }

  getFormError(key: string) {
    return getFormError(this.settingsForm.get(key));
  }

  get editEmail() {
    return this._editEmail;
  }
  set editEmail(editEmail: boolean) {
    this._editEmail = editEmail;

    if (this.email) {
      if (editEmail) {
        this.email.enable();
        this.email.setValue('');
      } else {
        this.email.disable();
        this.email.setValue(this.user.user?.email || '');
      }
    }
  }

  get editName() {
    return this._editName;
  }
  set editName(editName: boolean) {
    this._editName = editName;

    if (this.name) {
      if (editName) {
        this.name.enable();
        this.name.setValue('');
      } else {
        this.name.disable();
        this.name.setValue(this.user.user?.name || '');
      }
    }
  }

  get editPassword() {
    return this._editPassword;
  }
  set editPassword(editPassword: boolean) {
    this._editPassword = editPassword;

    if (this.password) {
      if (editPassword) {
        this.password.enable();
        this.password.setValue('');
      } else {
        this.password.disable();
        this.password.setValue('123456789');
      }
    }
  }

  async save() {
    this.settingsForm.markAllAsTouched();

    if (!this.user.user || this.settingsForm.invalid) {
      return;
    }

    this.saving = true;
    this.settingsForm.disable();
    this.error = null;
    this.dialogRef.disableClose = true;

    let updateValues: any = {
      oldPassword: this.currentPassword?.value,
    };

    if (this.editEmail) {
      updateValues.email = this.email?.value.trim();
    }
    if (this.editName) {
      updateValues.name = this.name?.value.trim();
    }
    if (this.editPassword) {
      updateValues.password = this.password?.value.trim();
    }

    let res = await this.api.updateUser(updateValues);

    this.saving = false;
    this.settingsForm.enable();
    this.dialogRef.disableClose = true;

    try {
      if (res.isOK()) {
        this.dialogRef.close();

        if ((await this.api.checkAuthentication()).isOK()) {
          this.snackbar.info('messages.users.settings_updated_successfully');
        } else {
          location.reload();
        }
      } else if (res.isConflict()) {
        throw new Error('messages.users.email_already_taken');
      } else if (res.isForbidden()) {
        throw new Error('messages.users.entered_password_wrong');
      } else {
        throw new Error(res.error?.info || undefined);
      }
    } catch (e: any) {
      this.error = e.message || 'messages.error_occurred';
      console.error('Error saving settings:', res.error);
    }
  }

  async deleteUser() {
    let doDelete = await this.dialog
      .open(ConfirmDialogComponent, {
        data: {
          translate: true,
          translationKey: 'dialogs.delete_account',
          warn: true,
        },
      })
      .afterClosed()
      .toPromise();

    if (!doDelete) return;

    this.saving = true;
    this.settingsForm.disable();

    let res = await this.api.deleteUser();

    if (res.isOK()) {
      this.user.logout('accountDeleted', '/home');
    } else {
      let error = '';

      if (res.error.info) {
        error = ': ' + res.error.info;
      }

      this.snackbar.error(
        this.translation.translate('messages.users.error_deleting_account') + error,
        undefined,
        false
      );
      console.error('Error deleting account:', res.error);
    }

    this.saving = false;
    this.settingsForm.enable();
  }

  ngOnDestroy() {
    this.subSink.clear();
  }
}
