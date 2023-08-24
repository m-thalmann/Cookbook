<?php

namespace Tests\Feature\Api\Auth;

use App\Models\AuthToken;
use Illuminate\Support\Arr;
use Tests\TestCase;
use TokenAuth\Enums\TokenType;
use TokenAuth\Facades\TokenAuth;

class AuthTokenTest extends TestCase {
    public function testShowAllRefreshTokensForUserSucceeds() {
        $user = $this->createAndLoginUser();
        $otherUser = $this->createUser();

        $createdTokens = 2;

        for ($i = 0; $i < $createdTokens; $i++) {
            $user->createToken(TokenType::REFRESH)->build();
        }

        // access tokens should not be included
        $user->createToken(TokenType::ACCESS)->build();

        // Tokens for other users should not be included
        $otherUser->createToken(TokenType::REFRESH)->build();

        // Revoked tokens should not be included
        $revokedToken = $user
            ->createToken(TokenType::REFRESH)
            ->build(save: false);
        $revokedToken->token->revoke()->store();

        // Expired tokens should not be included
        $expiredToken = $user
            ->createToken(TokenType::REFRESH)
            ->build(save: false);
        $expiredToken->token->expires_at = now()->subMinute();
        $expiredToken->token->store();

        $response = $this->getJson('/api/v1/auth/tokens');

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'type',
                'authenticatable_type',
                'authenticatable_id',
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

        /**
         * @var AuthToken
         */
        $token = $user->createToken(TokenType::ACCESS)->build()->token;
        $token->unsetRelation('authenticatable');

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

        /**
         * @var AuthToken
         */
        $token = $user->createToken(TokenType::REFRESH)->build()->token;
        $token->unsetRelation('authenticatable');

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

        $tokenId = $user1->createToken(TokenType::ACCESS)->build()->token->id;

        $response = $this->getJson("/api/v1/auth/tokens/{$tokenId}");
        $response->assertNotFound();
    }

    public function testShowAllTokensInGroupForUserSucceeds() {
        $user = $this->createAndLoginUser();
        $otherUser = $this->createUser();

        $createdTokens = 10;

        $groupId = AuthToken::generateGroupId($user);

        for ($i = 0; $i < $createdTokens; $i++) {
            $user
                ->createToken(
                    Arr::random([TokenType::ACCESS, TokenType::REFRESH])
                )
                ->setGroupId($groupId)
                ->build();
        }

        $revokedToken = $user
            ->createToken(TokenType::ACCESS)
            ->setGroupId($groupId)
            ->build(save: false);
        $revokedToken->token->revoke()->store();
        $createdTokens++; // should be included

        $expiredToken = $user
            ->createToken(TokenType::ACCESS)
            ->setGroupId($groupId)
            ->build(save: false);
        $expiredToken->token->expires_at = now()->subMinute();
        $expiredToken->token->store();
        $createdTokens++; // should be included

        // Tokens of other group should not be included
        $user
            ->createToken(TokenType::ACCESS)
            ->setGroupId($groupId + 1)
            ->build();

        // Tokens for other users should not be included even if they have the same group id
        $otherUser
            ->createToken(TokenType::ACCESS)
            ->setGroupId($groupId)
            ->build();

        $response = $this->getJson("/api/v1/auth/tokens/groups/$groupId");

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'type',
                'authenticatable_type',
                'authenticatable_id',
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

        $token = $user->createToken(TokenType::ACCESS)->build();

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

        $token = $user->createToken(TokenType::REFRESH)->build();

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

        $tokenId = $user1->createToken(TokenType::ACCESS)->build()->token->id;

        $response = $this->deleteJson("/api/v1/auth/tokens/{$tokenId}");
        $response->assertNotFound();
    }

    public function testDeleteAllTokensForUserSucceeds() {
        $user = $this->createAndLoginUser();

        $createdTokens = 10;

        for ($i = 0; $i < $createdTokens; $i++) {
            $user
                ->createToken(
                    Arr::random([TokenType::ACCESS, TokenType::REFRESH])
                )
                ->build();
        }

        $this->assertEquals($createdTokens, $user->tokens()->count());

        $response = $this->deleteJson('/api/v1/auth/tokens');

        $response->assertNoContent();

        $this->assertEquals(0, $user->tokens()->count());
    }
}
