<?php

namespace App\Models;

use App\Traits\Models\SerializesDatesToTimestamp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TokenAuth\Enums\TokenType;
use TokenAuth\Models\AuthToken as BaseAuthToken;
use TokenAuth\Support\NewAuthToken;
use TokenAuth\Support\NewAuthTokenPair;
use TokenAuth\Support\TransientAuthToken;

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

    public static function createDemoPair(User $user) {
        $demoAccessToken = new TransientAuthToken();
        $demoAccessToken->type = TokenType::ACCESS;
        $demoAccessToken->authenticatable = $user;

        $newDemoAccessToken = new NewAuthToken(
            $demoAccessToken,
            AuthToken::DEMO_TOKEN
        );

        $demoRefreshToken = new TransientAuthToken();
        $demoRefreshToken->type = TokenType::REFRESH;
        $demoRefreshToken->authenticatable = $user;

        $newDemoRefreshToken = new NewAuthToken($demoRefreshToken, '');

        return new NewAuthTokenPair($newDemoAccessToken, $newDemoRefreshToken);
    }
}
