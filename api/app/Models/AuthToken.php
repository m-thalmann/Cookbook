<?php

namespace App\Models;

use App\Traits\SerializesDateToTimestamp;
use TokenAuth\Models\AuthToken as BaseAuthToken;

class AuthToken extends BaseAuthToken {
    use SerializesDateToTimestamp;

    protected $hidden = ['token'];
}
