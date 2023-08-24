<?php

namespace App\Models;

use App\Traits\Models\SerializesDatesToTimestamp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TokenAuth\Models\AuthToken as BaseAuthToken;

class AuthToken extends BaseAuthToken {
    use SerializesDatesToTimestamp;

    const DEMO_TOKEN = 'demo-auth-token';

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
