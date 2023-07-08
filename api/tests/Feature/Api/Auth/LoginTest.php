<?php

namespace Tests\Feature\Api\Auth;

use Tests\TestCase;

class LoginTest extends TestCase {
    public function testLoginSucceedsWithCorrectCredentials() {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'user' => $user->toArray(),
            ],
        ]);
        $response->assertJsonStructure([
            'data' => [
                'user' => ['name', 'email', 'id'],
                'access_token',
                'refresh_token',
            ],
        ]);
    }

    public function testLoginAndAuthenticateWithReceivedAccessToken() {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        $response = $this->getJson('/api/v1/auth', [
            'Authorization' => "Bearer $accessToken",
        ]);

        $response->assertOk();
        $response->assertJson(['data' => $user->toArray()]);
    }

    public function testLoginAndAuthenticateWithReceivedRefreshToken() {
        $user = $this->createUser();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $refreshToken = $loginResponse->json('data.refresh_token');

        $response = $this->postJson(
            '/api/v1/auth/refresh',
            [],
            [
                'Authorization' => "Bearer $refreshToken",
            ]
        );

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => ['refresh_token', 'access_token'],
        ]);
    }

    public function testLoginFailsWithValidationErrors() {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'no-email',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function testLoginFailsWithBadCredentials() {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'not@exists.com',
            'password' => '12345678',
        ]);

        $response->assertUnauthorized();
    }
}
