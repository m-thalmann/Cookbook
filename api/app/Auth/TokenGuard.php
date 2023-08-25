<?php

namespace App\Auth;

use App\Models\AuthToken;
use App\Models\User;
use TokenAuth\Contracts\AuthTokenContract;
use TokenAuth\Support\TokenGuard as BaseTokenGuard;

class TokenGuard extends BaseTokenGuard {
    protected function getTokenInstance(string $token): ?AuthTokenContract {
        if ($token === AuthToken::DEMO_TOKEN) {
            return AuthToken::createDemoPair(
                User::query()
                    ->demoUser()
                    ->first()
            )->accessToken->token;
        }

        return parent::getTokenInstance($token);
    }
}
