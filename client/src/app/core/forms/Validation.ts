import { AbstractControl } from '@angular/forms';
import { TranslationObject } from '../i18n/translation.service';

export const getFormError = (field: AbstractControl | null): TranslationObject | null => {
  if (field?.invalid) {
    if (field.hasError('required')) {
      return { key: 'messages.form_validation.required' };
    }
    if (field.hasError('email')) {
      return { key: 'messages.form_validation.email' };
    }
    if (field.hasError('passwordsMismatch')) {
      return { key: 'messages.form_validation.passwords_mismatch' };
    }
    if (field.hasError('maxlength')) {
      return {
        key: 'messages.form_validation.max_length',
        replacements: { maxLength: field.getError('maxlength').requiredLength },
      };
    }
    if (field.hasError('min')) {
      return { key: 'messages.form_validation.min', replacements: { min: field.getError('min').min } };
    }
    if (field.hasError('max')) {
      return { key: 'messages.form_validation.max', replacements: { max: field.getError('max').max } };
    }
  }

  return null;
};

export const minArrayLength = (min: number) => {
  return (control: AbstractControl) => {
    if (control.value.length < min) {
      return { minArrayLength: true };
    }

    return null;
  };
};
