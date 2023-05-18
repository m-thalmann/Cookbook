import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component } from '@angular/core';
import { MatIconModule } from '@angular/material/icon';
import { Router } from '@angular/router';
import { TranslocoModule } from '@ngneat/transloco';
import { BehaviorSubject } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { EditRecipeFormData } from 'src/app/core/models/recipe';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { EditRecipeFormComponent } from '../components/edit-recipe-form/edit-recipe-form.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-create-recipe-page',
  standalone: true,
  imports: [CommonModule, TranslocoModule, MatIconModule, EditRecipeFormComponent],
  templateUrl: './create-recipe-page.component.html',
  styleUrls: ['./create-recipe-page.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CreateRecipePageComponent {
  saving$ = new BehaviorSubject<boolean>(false);
  serverErrorResponse$ = new BehaviorSubject<HttpErrorResponse | null>(null);

  constructor(private router: Router, private api: ApiService, private snackbar: SnackbarService) {}

  async onSave(recipe: EditRecipeFormData) {
    if (this.saving$.value) {
      return;
    }

    this.serverErrorResponse$.next(null);
    this.saving$.next(true);

    try {
      const response = await toPromise(this.api.recipes.create(recipe));

      this.snackbar.info('messages.recipeCreated', { translateMessage: true });

      this.router.navigate(['/recipes', response.body!.data.id, 'edit']);
    } catch (e) {
      if (e instanceof HttpErrorResponse) {
        this.serverErrorResponse$.next(e);
      } else {
        const errorMessage = this.snackbar.exception(e, {});

        Logger.error('Error creating recipe:', errorMessage, e);
      }
    }

    this.saving$.next(false);
  }
}

