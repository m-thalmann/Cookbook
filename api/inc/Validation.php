<?php

namespace API\Inc;

use PAF\Model\ValidationError;

class Validation {
    public static function getValidationErrorMessages($model, $mapping) {
        $errors = $model->getValidationErrors();

        $res = [];

        foreach ($errors as $prop => $error) {
            if (array_key_exists($prop, $mapping)) {
                $res[$prop] = Validation::getValidationErrorMessage(
                    $error->getError(),
                    $mapping[$prop][0],
                    $mapping[$prop][1] ?? false
                );
            }
        }

        return $res;
    }

    public static function getValidationErrorMessage(
        $error,
        $property,
        $isString = false
    ) {
        switch ($error) {
            case ValidationError::INVALID_NULL:
                return "'$property' must have a value";
                break;
            case ValidationError::INVALID_TYPE:
                return "'$property' has a wrong type";
                break;
            case ValidationError::INVALID_EMAIL:
                return "'$property' is not a valid email";
                break;
            case ValidationError::INVALID_URL:
                return "'$property' is not a valid URL";
                break;
            case ValidationError::INVALID_IP:
                return "'$property' is not a valid IP";
                break;
            case ValidationError::INVALID_ENUM:
            case ValidationError::INVALID_PATTERN:
                return "'$property' has an unallowed value";
                break;
            case ValidationError::INVALID_MIN:
                if ($isString) {
                    return "'$property' is too short";
                } else {
                    return "'$property' is too small";
                }
                break;
            case ValidationError::INVALID_MAX:
                if ($isString) {
                    return "'$property' is too long";
                } else {
                    return "'$property' is too big";
                }
                break;
        }

        return null;
    }
}
