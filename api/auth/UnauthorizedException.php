<?php

namespace API\Auth;

class UnauthorizedException extends \Exception {
    public function __construct($message = "Unauthorized") {
        parent::__construct($message);
    }
}
