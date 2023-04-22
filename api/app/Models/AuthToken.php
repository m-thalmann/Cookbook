<?php

namespace App\Models;

use App\Traits\Models\SerializesDatesToTimestamp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TokenAuth\Models\AuthToken as BaseAuthToken;
use TokenAuth\TokenAuth;

class AuthToken extends BaseAuthToken {
    use SerializesDatesToTimestamp;

    public function scopeNotExpired($query) {
        $query->where(function ($query) {
            $query->orWhere('expires_at', '>', now());
            $query->orWhereNull('expires_at');
        });
    }

    public function scopeNotRevoked($query) {
        $query->whereNull('revoked_at');
    }

    public function scopeActive($query) {
        $this->scopeNotExpired($query);
        $this->scopeNotRevoked($query);
    }

    public function scopeRefreshTokens($query) {
        $query->where('type', TokenAuth::TYPE_REFRESH);
    }

    public function setRequestAttributes(Request $request) {
        $this->ip_address = $request->ip();
        $this->user_agent = $request->userAgent();

        $ipHost = @gethostbyaddr($this->ip_address);

        if ($ipHost === false || Str::is($ipHost, $this->ip_address)) {
            $ipHost = null;
        }

        $this->ip_host = $ipHost;

        return $this;
    }
}
