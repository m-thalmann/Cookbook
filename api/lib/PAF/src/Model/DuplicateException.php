<?php

namespace PAF\Model;

/**
 * This exception is thrown, if a model is saved and an unique-constraint fails
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class DuplicateException extends \Exception {
    public function __construct($message) {
        parent::__construct($message);
    }
}
