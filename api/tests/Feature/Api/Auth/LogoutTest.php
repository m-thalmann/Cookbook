<?php

namespace Tests\Feature\Api\Auth;

use App\Models\AuthToken;
use Tests\TestCase;
use TokenAuth\Facades\TokenAuth;

class LogoutTest extends TestCase {
    public function testLogoutSuccess() {
        $user = $this->createUser();

        $newTokenPair = TokenAuth::createTokenPair($user)->buildPair();

        $response = $this->postJson(
            '/api/v1/auth/logout',
            [],
            [
                'Authorization' => "Bearer {$newTokenPair->accessToken->plainTextToken}",
            ]
        );

        $response->assertNoContent();

        TokenAuth::actingAs(null);

        $newResponse = $this->getJson('/api/v1/auth', [
            'Authorization' => "Bearer {$newTokenPair->accessToken->plainTextToken}",
        ]);
        $newResponse->assertUnauthorized();

        /**
         * @var AuthToken
         */
        $refreshToken = $newTokenPair->refreshToken->token;
        /**
         * @var AuthToken
         */
        $accessToken = $newTokenPair->accessToken->token;

        $this->assertNull($refreshToken->fresh());
        $this->assertNull($accessToken->fresh());
    }
}
