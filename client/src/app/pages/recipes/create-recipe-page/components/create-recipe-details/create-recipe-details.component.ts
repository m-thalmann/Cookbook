import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { ChangeDetectionStrategy, Component, Input, Output } from '@angular/core';
import { Router } from '@angular/router';
import { BehaviorSubject } from 'rxjs';
import { ApiService } from 'src/app/core/api/api.service';
import { Logger as LoggerClass } from 'src/app/core/helpers/logger';
import { toPromise } from 'src/app/core/helpers/to-promise';
import { EditRecipeFormData } from 'src/app/core/models/recipe';
import { SnackbarService } from 'src/app/core/services/snackbar.service';
import { EditRecipeDetailsFormComponent } from '../../../components/edit-recipe-details-form/edit-recipe-details-form.component';

const Logger = new LoggerClass('Recipes');

@Component({
  selector: 'app-create-recipe-details',
  standalone: true,
  imports: [CommonModule, EditRecipeDetailsFormComponent],
  templateUrl: './create-recipe-details.component.html',
  styleUrls: ['./create-recipe-details.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class CreateRecipeDetailsComponent {
  @Input() disabled = false;

  @Output() saving = new BehaviorSubject<boolean>(false);

  serverErrorResponse$ = new BehaviorSubject<HttpErrorResponse | null>(null);

  constructor(private router: Router, private api: ApiService, private snackbar: SnackbarService) {}

  async onSave(recipe: EditRecipeFormData) {
    if (this.saving.value || this.disabled) {
      return;
    }

    this.serverErrorResponse$.next(null);
    this.saving.next(true);

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

    this.saving.next(false);
  }
}

