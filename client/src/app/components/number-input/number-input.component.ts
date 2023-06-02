import { ChangeDetectionStrategy, Component, Input } from '@angular/core';
import { ControlValueAccessor, FormsModule, NG_VALUE_ACCESSOR } from '@angular/forms';
import { MatIconModule } from '@angular/material/icon';
import { CoerceBooleanProperty } from 'src/app/core/helpers/coerce-boolean-property';
import { AmountPipe } from 'src/app/core/pipes/amount.pipe';

@Component({
  selector: 'app-number-input',
  templateUrl: './number-input.component.html',
  styleUrls: ['./number-input.component.scss'],
  standalone: true,
  imports: [MatIconModule, FormsModule, AmountPipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: NumberInputComponent,
      multi: true,
    },
  ],
})
export class NumberInputComponent implements ControlValueAccessor {
  @Input() min?: number;
  @Input() max?: number;

  @Input() step?: number;

  @Input() stepFunction?: (currentValue: number, direction: 'up' | 'down') => number;

  @Input()
  @CoerceBooleanProperty()
  inputEnabled: any = true;

  @Input() defaultValue?: number;

  value: number = 0;
  isDisabled = false;

  private onChange = (_: number) => {};
  private onTouched = () => {};

  constructor() {}

  get stepSize() {
    return typeof this.step !== 'undefined' ? this.step : 1;
  }

  onInputChange(e: any) {
    const strValue: string = e.target.value;

    let value = parseFloat(strValue.replace(',', '.'));

    if (isNaN(value)) {
      if (typeof this.defaultValue !== 'undefined') {
        value = this.defaultValue;
      } else if (typeof this.min !== 'undefined') {
        value = this.min;
      } else {
        value = 0;
      }
    }

    this.setValue(value);

    this.onChange(this.value);
    this.onTouched();
  }

  addToValue(amount: number) {
    this.setValue(this.value + amount);

    this.onChange(this.value);
    this.onTouched();
  }

  decrementValue() {
    if (this.stepFunction) {
      this.addToValue(this.stepFunction(this.value, 'down'));
      return;
    }

    this.addToValue(this.stepSize * -1);
  }

  incrementValue() {
    if (this.stepFunction) {
      this.addToValue(this.stepFunction(this.value, 'up'));
      return;
    }

    this.addToValue(this.stepSize);
  }

  setValue(value: number) {
    if (typeof this.min !== 'undefined' && value < this.min) {
      value = this.min;
    }
    if (typeof this.max !== 'undefined' && value > this.max) {
      value = this.max;
    }

    this.value = value;
  }

  writeValue(value: number) {
    this.setValue(value);
  }

  registerOnChange(fn: (value: number) => void) {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void) {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean) {
    this.isDisabled = isDisabled;
  }
}
