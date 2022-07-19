<?php

namespace Tests\Feature\Auth;

use App\Models\AuthToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TokenAuth\TokenAuth;

class RefreshTokenTest extends TestCase {
    use RefreshDatabase;

    public function testRefreshingTokenSucceeds() {
        $user = $this->createUser();

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            'name1',
            'name2'
        );

        $response = $this->post(
            '/v1/auth/refresh',
            [],
            [
                'Authorization' => "Bearer {$refreshToken->plainTextToken}",
            ]
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => ['refresh_token', 'access_token'],
        ]);

        $oldRefreshToken = $refreshToken->token;
        $oldRefreshToken->refresh();

        $this->assertTrue($oldRefreshToken->isRevoked());

        $newRefreshToken = AuthToken::findRefreshToken(
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

        [$refreshToken, $accessToken] = TokenAuth::createTokenPairForUser(
            $user,
            'name1',
            'name2'
        );

        $response = $this->post(
            '/v1/auth/refresh',
            [],
            [
                'Authorization' => "Bearer {$accessToken->plainTextToken}",
            ]
        );

        $response->assertUnauthorized();
    }
}
