import { HttpErrorResponse } from '@angular/common/http';
import { FormGroup } from '@angular/forms';

export class ServerValidationHelper {
  static setValidationErrors(errorResponse: HttpErrorResponse, form: FormGroup, fieldsMap?: { [key: string]: string }) {
    let foundError = false;

    if (errorResponse.status === 422) {
      const validationErrors = errorResponse.error.errors;

      Object.keys(validationErrors).forEach((controlName) => {
        let formControlName = controlName;

        if (fieldsMap && fieldsMap[controlName]) {
          formControlName = fieldsMap[controlName];
        }

        const formControl = form.get(formControlName);

        if (formControl) {
          foundError = true;

          formControl.setErrors({
            serverError: validationErrors[controlName][0],
          });
        }
      });
    }

    return foundError;
  }
}
