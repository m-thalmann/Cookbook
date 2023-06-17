import { CommonModule } from '@angular/common';
import { HttpErrorResponse, HttpStatusCode } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, Inject } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MAT_DIALOG_DATA, MatDialogModule, MatDialogRef } from '@angular/material/dialog';
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
  selector: 'app-search-user-dialog',
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
  templateUrl: './search-user-dialog.component.html',
  styleUrls: ['./search-user-dialog.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class SearchUserDialogComponent {
  private subSink = new Subscription();

  foundUser$ = new BehaviorSubject<User | null>(null);

  searching$ = new BehaviorSubject<boolean>(false);

  searchingError$ = new BehaviorSubject<string | null>(null);

  constructor(
    @Inject(MAT_DIALOG_DATA)
    public data: {
      icon?: string;
      title?: string;
      selectIcon?: string;
    },
    private dialogRef: MatDialogRef<SearchUserDialogComponent>,
    private api: ApiService,
    private transloco: TranslocoService
  ) {
    this.subSink.add(
      this.searching$.subscribe((searching) => {
        this.dialogRef.disableClose = searching;
      })
    );
  }

  get icon() {
    return this.data?.icon ?? 'person_search';
  }

  get title() {
    return this.data?.title ?? this.transloco.translate('users.searchUser');
  }

  get selectIcon() {
    return this.data?.selectIcon ?? 'check_circle_outline';
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

