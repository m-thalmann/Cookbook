<?php

namespace API\inc;

use PAF\Model\Model;
use PAF\Model\ValidationError;

class Validation {
    /**
     * Gets all error messages (validation errors for properties)
     * for the given model. The model must have a "VALIDATION_PROPERTIES" constant
     *
     * @param Model $model The model to validate
     *
     * @return array The error messages (value) for the properties (key)
     */
    public static function getErrorMessages($model) {
        $res = [];

        if (!defined(get_class($model) . "::VALIDATION_PROPERTIES")) {
            return $res;
        }

        $validationProperties = $model::VALIDATION_PROPERTIES;

        $errors = $model->getValidationErrors();

        foreach ($errors as $prop => $error) {
            if (array_key_exists($prop, $validationProperties)) {
                $res[$prop] = self::getErrorMessage(
                    $error->getError(),
                    $validationProperties[$prop]
                );
            }
        }

        return $res;
    }

    /**
     * Returns the error message for a given error code
     *
     * @param int $error The validation error
     * @param bool $isString Whether the property is a string
     *
     * @see ValidationError
     *
     * @return string|null The validation error
     */
    public static function getErrorMessage($error, $isString = false) {
        switch ($error) {
            case ValidationError::INVALID_NULL:
                return "validation_error.null";
            case ValidationError::INVALID_TYPE:
                return "validation_error.type";
            case ValidationError::INVALID_EMAIL:
                return "validation_error.email";
            case ValidationError::INVALID_URL:
                return "validation_error.url";
            case ValidationError::INVALID_IP:
                return "validation_error.ip";
            case ValidationError::INVALID_ENUM:
            case ValidationError::INVALID_PATTERN:
                return "validation_error.value";
            case ValidationError::INVALID_MIN:
                if ($isString) {
                    return "validation_error.too_short";
                } else {
                    return "validation_error.too_small";
                }
            case ValidationError::INVALID_MAX:
                if ($isString) {
                    return "validation_error.too_long";
                } else {
                    return "validation_error.too_big";
                }
        }

        return null;
    }
}
