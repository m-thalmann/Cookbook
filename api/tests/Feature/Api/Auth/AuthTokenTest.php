<?php

namespace Tests\Feature\Api\Auth;

use Illuminate\Support\Arr;
use Tests\TestCase;
use TokenAuth\TokenAuth;

class AuthTokenTest extends TestCase {
    public function testShowAllRefreshTokensForUserSucceeds() {
        $user = $this->createAndLoginUser();
        $otherUser = $this->createUser();

        $createdTokens = 2;

        for ($i = 0; $i < $createdTokens; $i++) {
            $user->createToken(TokenAuth::TYPE_REFRESH, 'TestToken');
        }

        // access tokens should not be included
        $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken');

        // Tokens for other users should not be included
        $otherUser->createToken(TokenAuth::TYPE_REFRESH, 'TestToken');

        // Revoked tokens should not be included
        $revokedToken = $user->createToken(
            TokenAuth::TYPE_REFRESH,
            'TestToken'
        );
        $revokedToken->token->revoke()->save();

        // Expired tokens should not be included
        $expiredToken = $user->createToken(
            TokenAuth::TYPE_REFRESH,
            'TestToken'
        );
        $expiredToken->token->update(['expires_at' => now()->subMinute()]);

        $response = $this->getJson('/api/v1/auth/tokens');

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'type',
                'tokenable_type',
                'tokenable_id',
                'group_id',
                'name',
                'abilities' => [],
                'created_at',
                'updated_at',
                'is_current',
            ],
            $createdTokens,
            $response
        );
    }

    public function testShowSpecificAccessTokenForUserSucceeds() {
        $user = $this->createAndLoginUser();

        $token = $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken')->token;

        $response = $this->getJson("/api/v1/auth/tokens/{$token->id}");

        $response->assertOk();

        $expectedResponse = $token->toArray();

        $response->assertJson(['data' => $expectedResponse]);
        $response->assertJsonStructure([
            'data' => [...array_keys($expectedResponse), 'is_current'],
        ]);
    }

    public function testShowSpecificRefreshTokenForUserSucceeds() {
        $user = $this->createAndLoginUser();

        $token = $user->createToken(TokenAuth::TYPE_REFRESH, 'TestToken')
            ->token;

        $response = $this->getJson("/api/v1/auth/tokens/{$token->id}");

        $response->assertOk();

        $expectedResponse = $token->toArray();

        $response->assertJson(['data' => $expectedResponse]);
        $response->assertJsonStructure([
            'data' => [...array_keys($expectedResponse), 'is_current'],
        ]);
    }

    public function testShowSpecificTokenForOtherUserFails() {
        $user1 = $this->createUser();
        $user2 = $this->createAndLoginUser();

        $tokenId = $user1->createToken(TokenAuth::TYPE_ACCESS, 'TestToken')
            ->token->id;

        $response = $this->getJson("/api/v1/auth/tokens/{$tokenId}");
        $response->assertNotFound();
    }

    public function testShowAllTokensInGroupForUserSucceeds() {
        $user = $this->createAndLoginUser();
        $otherUser = $this->createUser();

        $createdTokens = 10;

        $groupId = TokenAuth::getNextTokenGroupId($user);

        for ($i = 0; $i < $createdTokens; $i++) {
            $user->createToken(
                Arr::random([TokenAuth::TYPE_ACCESS, TokenAuth::TYPE_REFRESH]),
                'TestToken',
                $groupId
            );
        }

        $revokedToken = $user->createToken(
            TokenAuth::TYPE_ACCESS,
            'TestToken',
            $groupId
        );
        $revokedToken->token->revoke()->save();
        $createdTokens++; // should be included

        $expiredToken = $user->createToken(
            TokenAuth::TYPE_ACCESS,
            'TestToken',
            $groupId
        );
        $expiredToken->token->update(['expires_at' => now()->subMinute()]);
        $createdTokens++; // should be included

        // Tokens of other group should not be included
        $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken', $groupId + 1);

        // Tokens for other users should not be included even if they have the same group id
        $otherUser->createToken(TokenAuth::TYPE_ACCESS, 'TestToken', $groupId);

        $response = $this->getJson("/api/v1/auth/tokens/groups/$groupId");

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'type',
                'tokenable_type',
                'tokenable_id',
                'group_id',
                'name',
                'abilities' => [],
                'created_at',
                'updated_at',
                'is_current',
            ],
            $createdTokens,
            $response
        );
    }

    public function testDeleteSpecificAccessTokenForUserSucceeds() {
        $user = $this->createAndLoginUser();

        $token = $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken');

        $response = $this->deleteJson(
            "/api/v1/auth/tokens/{$token->token->id}"
        );

        $response->assertNoContent();

        TokenAuth::actingAs(null);

        $newResponse = $this->getJson('/api/v1/auth', [
            'Authorization' => "Bearer {$token->plainTextToken}",
        ]);
        $newResponse->assertUnauthorized();
    }

    public function testDeleteSpecificRefreshTokenForUserSucceeds() {
        $user = $this->createAndLoginUser();

        $token = $user->createToken(TokenAuth::TYPE_REFRESH, 'TestToken');

        $response = $this->deleteJson(
            "/api/v1/auth/tokens/{$token->token->id}"
        );

        $response->assertNoContent();

        TokenAuth::actingAs(null);

        $newResponse = $this->postJson('/api/v1/auth/refresh', [
            'Authorization' => "Bearer {$token->plainTextToken}",
        ]);
        $newResponse->assertUnauthorized();
    }

    public function testDeleteSpecificTokenForOtherUserFails() {
        $user1 = $this->createUser();
        $user2 = $this->createAndLoginUser();

        $tokenId = $user1->createToken(TokenAuth::TYPE_ACCESS, 'TestToken')
            ->token->id;

        $response = $this->deleteJson("/api/v1/auth/tokens/{$tokenId}");
        $response->assertNotFound();
    }

    public function testDeleteAllTokensForUserSucceeds() {
        $user = $this->createAndLoginUser();

        $createdTokens = 10;

        for ($i = 0; $i < $createdTokens; $i++) {
            $user->createToken(
                Arr::random([TokenAuth::TYPE_ACCESS, TokenAuth::TYPE_REFRESH]),
                'TestToken'
            );
        }

        $this->assertEquals($createdTokens, $user->tokens()->count());

        $response = $this->deleteJson('/api/v1/auth/tokens');

        $response->assertNoContent();

        $this->assertEquals(0, $user->tokens()->count());
    }
}
