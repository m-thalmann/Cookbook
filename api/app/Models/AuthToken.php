<?php

namespace App\Models;

use App\Traits\Models\SerializesDatesToTimestamp;
use TokenAuth\Models\AuthToken as BaseAuthToken;
use TokenAuth\TokenAuth;

class AuthToken extends BaseAuthToken {
    use SerializesDatesToTimestamp;

    protected $hidden = ['token'];

    public function scopeNotExpired($query) {
        $query->where('expires_at', '>', now());
    }

    public function scopeNotRevoked($query) {
        $query->whereNull('revoked_at');
    }

    public function scopeActive($query) {
        $this->scopeNotExpired($query);
        $this->scopeNotRevoked($query);
    }

    public function scopeAccessTokens($query) {
        $query->where('type', TokenAuth::TYPE_ACCESS);
    }
}