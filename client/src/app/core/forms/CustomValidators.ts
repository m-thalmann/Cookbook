import { AbstractControl, ValidationErrors, ValidatorFn } from '@angular/forms';

const checkPasswords = (passwordControl: string, confirmControl: string): ValidatorFn => {
  return (group: AbstractControl): ValidationErrors | null => {
    let pass = group.get(passwordControl)!.value;
    let confirmPass = group.get(confirmControl)!.value;

    return pass === confirmPass ? null : { notSame: true };
  };
};

export const CustomValidators = {
  checkPasswords,
};
