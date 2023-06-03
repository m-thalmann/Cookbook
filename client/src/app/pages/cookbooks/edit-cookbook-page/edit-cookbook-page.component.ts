import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, EventEmitter } from '@angular/core';
import { MatButtonModule } from '@angular/material/button';
import { MatIconModule } from '@angular/material/icon';
import { ActivatedRoute, Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject, EMPTY, combineLatest, of, shareReplay, startWith, switchMap } from 'rxjs';
import { ErrorDisplayComponent } from 'src/app/components/error-display/error-display.component';
import { ApiService } from 'src/app/core/api/api.service';
import { AuthService } from 'src/app/core/auth/auth.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
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
    private snackbar: SnackbarService
  ) {}

  onDetailsSaving(saving: boolean) {
    this.detailsSaving$.next(saving);
  }

  onUsersSaving(saving: boolean) {
    this.usersSaving$.next(saving);
  }

  async deleteCookbook() {
    // TODO: implement
  }
}
