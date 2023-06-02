import { Dialog } from '@angular/cdk/dialog';
import { CommonModule } from '@angular/common';
import { HttpResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, EventEmitter, Input, OnDestroy, Output } from '@angular/core';
import { MatDialog } from '@angular/material/dialog';
import { MatIconModule } from '@angular/material/icon';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TranslocoService } from '@ngneat/transloco';
import { BehaviorSubject, Subscription, catchError, finalize, throwError } from 'rxjs';
import { ConfirmDialogComponent } from 'src/app/components/dialogs/confirm-dialog/confirm-dialog.component';
import { ImageSliderDialogComponent } from 'src/app/components/image-slider-dialog/image-slider-dialog.component';
import { SkeletonComponent } from 'src/app/components/skeleton/skeleton.component';
import { ApiService } from 'src/app/core/api/api.service';
import { RepeatDirective } from 'src/app/core/directives/repeat.directive';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { DetailedRecipe } from 'src/app/core/models/recipe';
import { handledErrorInterceptor } from 'src/app/core/rxjs/handled-error-interceptor';
import { SnackbarService } from 'src/app/core/services/snackbar.service';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-edit-recipe-images',
  standalone: true,
  imports: [CommonModule, MatIconModule, MatProgressSpinnerModule, SkeletonComponent, RepeatDirective],
  templateUrl: './edit-recipe-images.component.html',
  styleUrls: ['./edit-recipe-images.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EditRecipeImagesComponent implements OnDestroy {
  private subSink = new Subscription();

  @Input()
  @CoerceBooleanProperty()
  disabled: any = false;

  @Input() recipe!: DetailedRecipe | null;

  @Output() updateRecipe = new EventEmitter<void>();
  @Output() saving = new BehaviorSubject<boolean>(false);

  uploadProgress$ = new BehaviorSubject<number | null>(null);

  constructor(
    private api: ApiService,
    private dialog: MatDialog,
    private cdkDialog: Dialog,
    private snackbar: SnackbarService,
    private transloco: TranslocoService
  ) {}

  async uploadImage(files: FileList | null) {
    if (this.recipe === null || !files || files.length !== 1 || this.saving.value || this.disabled) {
      return;
    }

    this.saving.next(true);
    this.uploadProgress$.next(0);

    this.subSink.add(
      this.api.recipes.images
        .create(this.recipe.id, files[0])
        .pipe(
          catchError((e) => {
            const errorMessage = this.snackbar.exception(e, {});

            Logger.error('Error uploading image:', errorMessage, e);

            return throwError(() => e);
          }),
          finalize(() => {
            this.saving.next(false);
            this.uploadProgress$.next(null);
          }),
          handledErrorInterceptor()
        )
        .subscribe((event) => {
          if (event instanceof HttpResponse) {
            this.updateRecipe.next();
            this.snackbar.info('messages.imageUploaded', { translateMessage: true });
          } else {
            this.uploadProgress$.next(event ?? 0);
          }
        })
    );
  }

  async deleteImage(imageId: number) {
    if (this.saving.value) {
      return;
    }

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

    this.saving.next(true);

    try {
      await toPromise(this.api.recipes.images.delete(imageId));

      this.updateRecipe.emit();

      this.snackbar.info('messages.imageDeleted', { translateMessage: true });
    } catch (e) {
      const errorMessage = this.snackbar.exception(e, {
        translateMessage: true,
      }).message;

      Logger.error('Error deleting image', errorMessage, e);
    }

    this.saving.next(false);
  }

  openImageSlider(index: number) {
    const imageUrls = this.recipe!.images.map((image) => image.url);

    this.cdkDialog.open(ImageSliderDialogComponent, {
      data: { images: imageUrls, startIndex: index },
    });
  }

  ngOnDestroy() {
    this.subSink.unsubscribe();
  }
}
