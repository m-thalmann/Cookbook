import { ChangeDetectionStrategy, Component, OnDestroy } from '@angular/core';
import { FormBuilder, FormControl, Validators } from '@angular/forms';
import { MatDialog } from '@angular/material/dialog';
import { BehaviorSubject, distinctUntilChanged, lastValueFrom, Subscription, take } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { PromptDialogComponent } from 'src/app/components/dialogs/prompt-dialog/prompt-dialog.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { CustomValidators } from 'src/app/core/forms/CustomValidators';

@Component({
  selector: 'app-account-settings-page',
  templateUrl: './account-settings-page.component.html',
  styleUrls: ['./account-settings-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AccountSettingsPageComponent implements OnDestroy {
  private subSink = new Subscription();

  profileForm = this.fb.group({
    name: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
    email: new FormControl<string>('', { nonNullable: true, validators: [Validators.required, Validators.email] }),
  });

  passwordForm = this.fb.group(
    {
      oldPassword: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
      newPassword: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
      confirmedPassword: new FormControl<string>('', { nonNullable: true, validators: [Validators.required] }),
    },
    { validators: CustomValidators.checkPasswords('newPassword', 'confirmedPassword') }
  );

  isLoading$ = new BehaviorSubject<boolean>(false);

  // TODO: error handling

  constructor(private auth: AuthService, private api: ApiService, private fb: FormBuilder, private dialog: MatDialog) {
    this.loadFormData();

    this.subSink.add(
      this.isLoading$.pipe(distinctUntilChanged()).subscribe((isLoading) => {
        if (isLoading) {
          this.profileForm.disable();
          this.passwordForm.disable();
        } else {
          this.profileForm.enable();
          this.passwordForm.enable();
        }
      })
    );
  }

  async loadFormData() {
    const user = await this.getUser();

    this.profileForm.patchValue({
      name: user.name,
      email: user.email,
    });
  }

  async updateProfile() {
    if (this.isLoading$.value) return;

    const user = await this.getUser();

    const hasChangedEmail = this.profileForm.value.email !== user.email;

    const updateData: { name?: string; email?: string; current_password?: string } = {
      name: this.profileForm.value.name,
      email: hasChangedEmail ? this.profileForm.value.email : undefined,
    };

    if (hasChangedEmail) {
      const confirmedPassword: string | null = await lastValueFrom(
        this.dialog
          .open(PromptDialogComponent, {
            data: {
              title: 'Confirm current password',
              type: 'password',
              btnConfirm: 'Confirm',
              btnDecline: 'Cancel',
            },
          })
          .afterClosed()
          .pipe(take(1))
      );

      if (!confirmedPassword) {
        return;
      }

      updateData['current_password'] = confirmedPassword;
    }

    this.isLoading$.next(true);

    try {
      await lastValueFrom(this.api.users.update(user.id, updateData).pipe(take(1)));
      // TODO: success notification

      await this.auth.initialize();

      this.isLoading$.next(false);
    } catch (e) {
      this.isLoading$.next(false);
      throw e;
    }
  }

  async updatePassword() {
    if (this.isLoading$.value) return;

    const user = await this.getUser();

    this.isLoading$.next(true);

    try {
      await lastValueFrom(
        this.api.users
          .update(user.id, {
            password: this.passwordForm.value.newPassword,
            current_password: this.passwordForm.value.oldPassword,
          })
          .pipe(take(1))
      );
      // TODO: success notification

      await this.auth.initialize();

      this.isLoading$.next(false);
    } catch (e) {
      this.isLoading$.next(false);
      throw e;
    }
  }

  async deleteAccount() {
    if (this.isLoading$.value) return;

    const user = await this.getUser();

    const confirmed = await lastValueFrom(
      this.dialog
        .open(ConfirmDialogComponent, {
          data: {
            title: 'Are you sure?',
            content: 'This action cannot be undone.',
            btnConfirm: 'Delete',
            btnDecline: 'Abort',
            warn: true,
          },
        })
        .afterClosed()
        .pipe(take(1))
    );

    if (!confirmed) {
      return;
    }

    this.isLoading$.next(true);

    try {
      await lastValueFrom(this.api.users.delete(user.id).pipe(take(1)));
      // TODO: success notification

      await this.auth.initialize();

      this.isLoading$.next(false);
    } catch (e) {
      this.isLoading$.next(false);
      throw e;
    }
  }

  private async getUser() {
    return (await lastValueFrom(this.auth.user$.pipe(take(1))))!;
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}

