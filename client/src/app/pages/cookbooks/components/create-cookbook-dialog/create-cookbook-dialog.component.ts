import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, Subscription, distinctUntilChanged } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { ServerValidationHelper } from 'src/app/core/forms/ServerValidationHelper';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Cookbooks');

@Component({
  selector: 'app-create-cookbook-dialog',
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatIconModule,
    MatDialogModule,
    MatButtonModule,
    ReactiveFormsModule,
    MatFormFieldModule,
    MatInputModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './create-cookbook-dialog.component.html',
  styleUrls: ['./create-cookbook-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CreateCookbookDialogComponent {
  private subSink = new Subscription();

  saving$ = new BehaviorSubject(false);

  form = this.fb.nonNullable.group({
    name: [<string>'', [Validators.required]],
  });

  constructor(
    private dialogRef: MatDialogRef<CreateCookbookDialogComponent>,
    private fb: FormBuilder,
    private api: ApiService,
    private router: Router,
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

    try {
      const response = await toPromise(this.api.cookbooks.create(this.form.controls.name.value));

      this.snackbar.info('messages.cookbookCreated', { translateMessage: true });

      this.router.navigate(['/cookbooks', response.body!.data.id, 'edit']);

      this.dialogRef.close();
    } catch (e) {
      this.saving$.next(false);

      if (!(e instanceof HttpErrorResponse && ServerValidationHelper.setValidationErrors(e, this.form))) {
        const errorMessage = this.snackbar.exception(e, {}).message;

        Logger.error('Error creating cookbook:', errorMessage, e);
      }
    }
  }
}
