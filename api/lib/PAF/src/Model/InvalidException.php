<?php

namespace PAF\Model;

/**
 * This exception is thrown, if a model is saved but it is invalid
 *
 * @see Model::getFirstValidationError()
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class InvalidException extends \Exception {
    /**
     * @param array ['property'] - string - name of the property <br/>
     *              ['error'] - ValidationError - validation error
     */
    public function __construct($validationError) {
        $msg = "Object is invalid: Validation error for property \"{$validationError['property']}\": ";

        if ($validationError['error']->isCustom()) {
            $msg .= $validationError['error']->getCustomError();
        } else {
            $msg .= $validationError['error']->getError();
        }

        parent::__construct($msg);
    }
}
