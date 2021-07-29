<?php

namespace PAF\Model;

/**
 * This exception is thrown, if a model is saved or reloaded after it was deleted
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class DeletedException extends \Exception {
    public function __construct() {
        parent::__construct('Object was deleted');
    }
}
