import { AbstractControl } from '@angular/forms';

export const getFormError = (field: AbstractControl | null) => {
  if (field?.invalid) {
    if (field.hasError('required')) {
      return 'This field is required';
    }
    if (field.hasError('email')) {
      return 'Not a valid email address';
    }
    if (field.hasError('passwordsMismatch')) {
      return "The passwords don't match";
    }
    if (field.hasError('maxlength')) {
      return `The value is too long (max. ${field.getError('maxlength').requiredLength})`;
    }
    if (field.hasError('min')) {
      return `The value is too small (min. ${field.getError('min').min})`;
    }
    if (field.hasError('max')) {
      return `The value is too big (max. ${field.getError('max').max})`;
    }
  }

  return null;
};
