import { CommonModule } from '@angular/common';
import {
  AfterViewInit,
  ChangeDetectionStrategy,
  Component,
  ElementRef,
  EventEmitter,
  Input,
  Output,
  ViewChild,
} from '@angular/core';
import { FormGroup, ReactiveFormsModule } from '@angular/forms';
import { MatAutocompleteModule, MatAutocompleteSelectedEvent } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { TranslocoModule } from '@ngneat/transloco';
import { EMPTY, Observable, ReplaySubject, combineLatest, map, shareReplay, startWith } from 'rxjs';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { SimpleIngredient } from 'src/app/core/models/ingredient';
import { FormIngredient } from '../../edit-recipe-details-form.component';

@Component({
  selector: 'app-edit-recipe-ingredient-form-group',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    TranslocoModule,
    MatFormFieldModule,
    MatInputModule,
    MatButtonModule,
    MatIconModule,
    MatAutocompleteModule,
  ],
  templateUrl: './edit-recipe-ingredient-form-group.component.html',
  styleUrls: ['./edit-recipe-ingredient-form-group.component.scss'],
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class EditRecipeIngredientFormGroupComponent implements AfterViewInit {
  @ViewChild('amountInput') amountInput!: ElementRef<HTMLInputElement>;

  @Input() formControlGroup!: FormGroup<FormIngredient>;
  @Input() ingredientKey!: string;

  @Input()
  @CoerceBooleanProperty()
  disabled: any = false;

  @Input()
  set ingredientOptions(ingredients: SimpleIngredient[] | null) {
    this.ingredientOptions$.next(ingredients);
  }

  private ingredientOptions$ = new ReplaySubject<SimpleIngredient[] | null>(1);

  @Output() addIngredient = new EventEmitter<void>();
  @Output() removeIngredient = new EventEmitter<void>();

  filteredIngredients$: Observable<SimpleIngredient[]> = EMPTY;

  ngAfterViewInit() {
    this.filteredIngredients$ = combineLatest([
      this.formControlGroup.controls.name.valueChanges.pipe(startWith(undefined)),
      this.ingredientOptions$,
    ]).pipe(
      map(([_, ingredients]) => {
        const name = this.formControlGroup.controls.name.value;

        // fixes intermediate state when autocomplete selects option
        if (typeof name !== 'string' && name !== null) {
          return [];
        }

        const filterName = (name || '').toLowerCase();

        return ingredients?.filter((ingredient) => ingredient.name.toLowerCase().includes(filterName)) || [];
      }),
      startWith([]),
      shareReplay({ bufferSize: 1, refCount: true })
    );
  }

  onIngredientKeyDownEnter(event: Event) {
    event.preventDefault();

    this.addIngredient.emit();
  }

  onIngredientAutocompleteSelected(event: MatAutocompleteSelectedEvent) {
    let ingredient = event.option.value as SimpleIngredient;

    this.formControlGroup.controls.name.setValue(ingredient.name);
    this.formControlGroup.controls.unit.setValue(ingredient.unit);

    setTimeout(() => {
      this.amountInput.nativeElement.focus();
    });
  }
}
