<?php

namespace PAF\Model;

/**
 * This exception is thrown, if a model is reloaded, but it was not yet saved to the database
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class NotSavedException extends \Exception {
    public function __construct() {
        parent::__construct('Object not yet saved to database');
    }
}
