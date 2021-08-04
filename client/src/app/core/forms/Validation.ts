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
      return 'The entered value is too long';
    }
  }

  return null;
};
