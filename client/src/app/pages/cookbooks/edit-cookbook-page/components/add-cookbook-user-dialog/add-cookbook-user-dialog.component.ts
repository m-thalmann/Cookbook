import { CommonModule } from '@angular/common';
import { HttpErrorResponse, HttpStatusCode } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialogModule, MatDialogRef } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, Subscription } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { User } from 'src/app/core/models/user';

@Component({
  selector: 'app-add-cookbook-user-dialog',
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatDialogModule,
    MatIconModule,
    MatButtonModule,
    MatFormFieldModule,
    MatInputModule,
    MatProgressSpinnerModule,
  ],
  templateUrl: './add-cookbook-user-dialog.component.html',
  styleUrls: ['./add-cookbook-user-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class AddCookbookUserDialogComponent {
  private subSink = new Subscription();

  foundUser$ = new BehaviorSubject<User | null>(null);

  searching$ = new BehaviorSubject<boolean>(false);

  searchingError$ = new BehaviorSubject<string | null>(null);

  constructor(
    private dialogRef: MatDialogRef<AddCookbookUserDialogComponent>,
    private api: ApiService,
    private transloco: TranslocoService
  ) {
    this.subSink.add(
      this.searching$.subscribe((searching) => {
        this.dialogRef.disableClose = searching;
      })
    );
  }

  async searchUser(username: string) {
    if (!username) {
      return;
    }

    this.foundUser$.next(null);
    this.searchingError$.next(null);
    this.searching$.next(true);

    const request = this.api.users.getByEmail(username);

    try {
      const userResponse = await toPromise(request);

      this.foundUser$.next(userResponse.body?.data ?? null);
    } catch (e) {
      let errorMessage: string | null = this.transloco.translate('messages.errors.unexpectedError');

      if (e instanceof HttpErrorResponse) {
        if (e.status === HttpStatusCode.NotFound) {
          errorMessage = this.transloco.translate('users.notFound');
        } else {
          errorMessage = this.api.getErrorMessage(e);
        }
      }

      this.searchingError$.next(errorMessage);
    }

    this.searching$.next(false);
  }
}

