import { Component } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { MatDialogRef } from '@angular/material/dialog';
import { MatSnackBar } from '@angular/material/snack-bar';
import { ApiService } from 'src/app/core/api/api.service';
import { UserService } from 'src/app/core/auth/user.service';
import { getFormError } from 'src/app/core/forms/Validation';

@Component({
  selector: 'cb-settings-dialog',
  templateUrl: './settings-dialog.component.html',
  styleUrls: ['./settings-dialog.component.scss'],
})
export class SettingsDialogComponent {
  settingsForm: FormGroup;

  private _editEmail = false;
  private _editName = false;
  private _editPassword = false;

  saving = false;
  error: string | null = null;

  constructor(
    private fb: FormBuilder,
    private api: ApiService,
    private user: UserService,
    private dialogRef: MatDialogRef<SettingsDialogComponent>,
    private snackBar: MatSnackBar
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
        current_password: ['', Validators.required],
        password: [
          { value: '123456789', disabled: true },
          () => {
            if (this.editPassword && this.password?.value.trim().length === 0) {
              return { required: true };
            }
            return null;
          },
        ],
        password_confirm: [
          '',
          () => {
            if (this.editPassword && this.password?.value != this.password_confirm?.value) {
              return { passwordsMismatch: true };
            }
            if (this.editPassword && this.password_confirm?.value.trim().length === 0) {
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

    this.password?.valueChanges.subscribe(() => {
      if (this.password_confirm) {
        this.password_confirm.updateValueAndValidity();
      }
    });
  }

  get email() {
    return this.settingsForm?.get('email');
  }
  get name() {
    return this.settingsForm?.get('name');
  }
  get current_password() {
    return this.settingsForm?.get('current_password');
  }
  get password() {
    return this.settingsForm?.get('password');
  }
  get password_confirm() {
    return this.settingsForm?.get('password_confirm');
  }

  getFormError(key: string) {
    let err = getFormError(this.settingsForm.get(key));

    return err;
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
      old_password: this.current_password?.value,
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
        this.snackBar.open('Successfully updated settings!', 'OK', {
          duration: 5000,
        });

        this.dialogRef.close();

        await this.api.checkAuthentication();
      } else if (res.isConflict()) {
        throw new Error('This email is already taken!');
      } else if (res.isForbidden()) {
        throw new Error('The entered password is wrong!');
      } else {
        throw new Error(res.error?.info || undefined);
      }
    } catch (e) {
      this.error = e.message || 'An error occured!';
    }
  }
}