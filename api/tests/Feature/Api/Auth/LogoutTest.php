<?php

namespace Tests\Feature\Api\Auth;

use Tests\TestCase;
use TokenAuth\TokenAuth;

class LogoutTest extends TestCase {
    public function testLogoutSuccess() {
        $user = $this->createUser();

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            'name1',
            'name2'
        );

        $response = $this->postJson(
            '/api/v1/auth/logout',
            [],
            [
                'Authorization' => "Bearer {$accessToken->plainTextToken}",
            ]
        );

        $response->assertNoContent();

        TokenAuth::actingAs(null);

        $newResponse = $this->getJson('/api/v1/auth', [
            'Authorization' => "Bearer {$accessToken->plainTextToken}",
        ]);
        $newResponse->assertUnauthorized();

        $refreshToken = $refreshToken->token;
        $accessToken = $accessToken->token;

        $this->assertNull($refreshToken->fresh());
        $this->assertNull($accessToken->fresh());
    }
}
