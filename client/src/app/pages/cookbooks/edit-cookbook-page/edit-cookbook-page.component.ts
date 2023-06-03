import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoModule, TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, EMPTY, combineLatest, of, shareReplay, startWith, switchMap } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { CookbookHeaderComponent } from '../components/cookbook-header/cookbook-header.component';
import { EditCookbookDetailsFormComponent } from './components/edit-cookbook-details-form/edit-cookbook-details-form.component';
import { ManageCookbookUsersComponent } from './components/manage-cookbook-users/manage-cookbook-users.component';

const Logger = new LoggerClass('Cookbooks');

@Component({
  selector: 'app-edit-cookbook-page',
  standalone: true,
  imports: [
    CommonModule,
    TranslocoModule,
    MatIconModule,
    MatButtonModule,
    CookbookHeaderComponent,
    EditCookbookDetailsFormComponent,
    ManageCookbookUsersComponent,
    ErrorDisplayComponent,
  ],
  templateUrl: './edit-cookbook-page.component.html',
  styleUrls: ['./edit-cookbook-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EditCookbookPageComponent {
  private cookbookId$ = combineLatest([this.activatedRoute.params, this.auth.user$]).pipe(
    switchMap(([params, _]) => {
      if (params['id']) {
        return of(params['id']);
      }

      Logger.error('EditCookbookPage: No id defined');

      this.router.navigate(['/']);

      return EMPTY;
    })
  );

  updateCookbook$ = new EventEmitter<void>();

  cookbook$ = combineLatest([this.cookbookId$, this.updateCookbook$.pipe(startWith(undefined))]).pipe(
    switchMap(([cookbookId, _]) => this.api.cookbooks.get(cookbookId)),
    switchMap((cookbook) => {
      if (cookbook.body?.data.meta.is_admin) {
        return of(cookbook);
      }

      Logger.error('EditCookbookPage: User cannot edit cookbook');
      this.snackbar.warn('messages.errors.userCantEditCookbook', { translateMessage: true });

      this.router.navigate(['/cookbooks', cookbook.body!.data.id]);

      return EMPTY;
    }),
    handledErrorInterceptor(),
    shareReplay({ bufferSize: 1, refCount: true })
  );

  error$ = this.api.handleRequestError(this.cookbook$, (error) =>
    Logger.error('Error while loading cookbook:', this.api.getErrorMessage(error), error)
  );

  detailsSaving$ = new BehaviorSubject(false);
  usersSaving$ = new BehaviorSubject(false);
  deleting$ = new BehaviorSubject(false);

  constructor(
    private api: ApiService,
    private activatedRoute: ActivatedRoute,
    private router: Router,
    private auth: AuthService,
    private snackbar: SnackbarService,
    private dialog: MatDialog,
    private transloco: TranslocoService
  ) {}

  onDetailsSaving(saving: boolean) {
    this.detailsSaving$.next(saving);
  }

  onUsersSaving(saving: boolean) {
    this.usersSaving$.next(saving);
  }

  async deleteCookbook() {
    const confirmed = await toPromise(
      this.dialog
        .open(ConfirmDialogComponent, {
          data: {
            title: this.transloco.translate('messages.areYouSure'),
            content: this.transloco.translate('messages.thisActionCantBeUndone'),
            btnConfirm: this.transloco.translate('actions.confirm'),
            btnDecline: this.transloco.translate('actions.abort'),
            warn: true,
          },
        })
        .afterClosed()
    );

    if (!confirmed) {
      return;
    }

    this.deleting$.next(true);

    const cookbookId = await toPromise(this.cookbookId$);

    try {
      await toPromise(this.api.cookbooks.delete(cookbookId));

      this.snackbar.info('messages.cookbookDeleted', { translateMessage: true });

      this.router.navigateByUrl('/cookbooks');
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {}).message;

      Logger.error('Error deleting cookbook:', errorMessage, e);
    }

    this.deleting$.next(false);
  }
}
