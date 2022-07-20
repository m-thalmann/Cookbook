<?php

namespace Tests\Feature\Auth\Api;

use App\Models\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TokenAuth\TokenAuth;

class LogoutTest extends TestCase {
    use RefreshDatabase;

    public function testLogoutSuccess() {
        $user = $this->createUser();

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            'name1',
            'name2'
        );

        $response = $this->postJson(
            '/v1/auth/logout',
            [],
            [
                'Authorization' => "Bearer {$accessToken->plainTextToken}",
            ]
        );

        $response->assertNoContent();

        TokenAuth::actingAs(null);

        $newResponse = $this->get('/v1/auth', [
            'Authorization' => "Bearer {$accessToken->plainTextToken}",
        ]);
        $newResponse->assertUnauthorized();

        $refreshToken = $refreshToken->token;
        $accessToken = $accessToken->token;

        $this->assertNull($refreshToken->fresh());
        $this->assertNull($accessToken->fresh());
    }
}
