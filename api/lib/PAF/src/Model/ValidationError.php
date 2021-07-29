<?php

namespace PAF\Model;

/**
 * This class represents a validation error
 *
 * @see Model::validateProperty()
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class ValidationError {
    /**
     * A custom validation error occurred
     */
    const INVALID_CUSTOM = -1;
    /**
     * The property does not exist
     */
    const INVALID_PROPERTY = 0;
    /**
     * The value of the property is null, but needs to have a value
     */
    const INVALID_NULL = 1;
    /**
     * The value of the property has the wrong type
     */
    const INVALID_TYPE = 2;
    /**
     * The value of the property is not a valid email
     */
    const INVALID_EMAIL = 3;
    /**
     * The value of the property is not a valid url
     */
    const INVALID_URL = 4;
    /**
     * The value of the property is not a valid ip
     */
    const INVALID_IP = 5;
    /**
     * The value of the property is not contained in the values set by the enum annotation
     */
    const INVALID_ENUM = 6;
    /**
     * The value of the property does not match the given pattern
     */
    const INVALID_PATTERN = 7;
    /**
     * Integers: The value of the property is too low | Strings: The value of the property is too short
     */
    const INVALID_MIN = 8;
    /**
     * Integers: The value of the property is too high | Strings: The value of the property is too long
     */
    const INVALID_MAX = 9;

    /**
     * @var int $error The validation error
     * @var mixed $customError The custom validation error
     */
    private $error;
    private $customError = null;

    /**
     * @param int $error The validation error
     */
    public function __construct($error) {
        $this->error = $error;
    }

    /**
     * Instanciates a ValidationError and sets the custom validation error
     *
     * @return ValidationError
     */
    public static function custom($customError) {
        $validationError = new ValidationError(self::INVALID_CUSTOM);
        $validationError->customError = $customError;

        return $validationError;
    }

    /**
     * Returns the validation error
     *
     * @return int
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Returns the custom validation error
     *
     * @return mixed
     */
    public function getCustomError() {
        return $this->customError;
    }

    /**
     * Returns whether the error is a custom validation error or not
     *
     * @return bool
     */
    public function isCustom() {
        return $this->error === self::INVALID_CUSTOM;
    }
}
