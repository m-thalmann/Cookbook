<?php

namespace Tests\Feature\Api\Auth;

use App\Models\AuthToken;
use Tests\TestCase;
use TokenAuth\Enums\TokenType;
use TokenAuth\Facades\TokenAuth;

class RefreshTokenTest extends TestCase {
    public function testRefreshingTokenSucceeds() {
        $user = $this->createUser();

        $newTokenPair = TokenAuth::createTokenPair($user)->buildPair();

        $response = $this->postJson(
            '/api/v1/auth/refresh',
            [],
            [
                'Authorization' => "Bearer {$newTokenPair->refreshToken->plainTextToken}",
            ]
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => ['refresh_token', 'access_token'],
        ]);

        /**
         * @var AuthToken
         */
        $oldRefreshToken = $newTokenPair->refreshToken->token;
        $oldRefreshToken->refresh();

        $this->assertTrue($oldRefreshToken->isRevoked());

        $newRefreshToken = AuthToken::find(
            TokenType::REFRESH,
            $response->json('data.refresh_token')
        );
        $this->assertNotNull($newRefreshToken);

        $this->assertEquals(
            $oldRefreshToken->group_id,
            $newRefreshToken->group_id
        );
    }

    public function testRefreshingTokenWithAccessTokenFails() {
        $user = $this->createUser();

        $newTokenPair = TokenAuth::createTokenPair($user)->buildPair();

        $response = $this->postJson(
            '/api/v1/auth/refresh',
            [],
            [
                'Authorization' => "Bearer {$newTokenPair->accessToken->plainTextToken}",
            ]
        );

        $response->assertUnauthorized();
    }
}
