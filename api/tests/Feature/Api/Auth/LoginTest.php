<?php

namespace Tests\Feature\Api\Auth;

use App\Http\Controllers\Auth\AuthenticationController;
use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

    public function testLoginSucceedsAndSetsHeaderWhenEmailNotVerified() {
        $user = $this->createUser(isEmailVerified: false);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertOk();
        $response->assertHeader(
            AuthenticationController::EMAIL_UNVERIFIED_HEADER,
            'true'
        );

        $accessToken = $response->json('data.access_token');

        $authResponse = $this->getJson('/api/v1/auth', [
            'Authorization' => "Bearer $accessToken",
        ]);

        $authResponse->assertOk();
        $authResponse->assertHeader(
            AuthenticationController::EMAIL_UNVERIFIED_HEADER,
            'true'
        );
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

    public function testLoginFailsInDemoModeWithNonDemoUser() {
        $this->setDemoEnv();

        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertUnauthorized();
    }

    public function testLoginReturnsDemoTokensForDemoUserAndAuthenticates() {
        $this->setDemoEnv();

        $user = User::factory()->create([
            'email' => User::DEMO_EMAIL,
            'password' => Hash::make(self::DEFAULT_USER_PASSWORD),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertOk();

        $accessToken = $response->json('data.access_token');
        $refreshToken = $response->json('data.refresh_token');

        $this->assertEquals(AuthToken::DEMO_TOKEN, $accessToken);
        $this->assertEquals('', $refreshToken);

        $authResponse = $this->getJson('/api/v1/auth', [
            'Authorization' => "Bearer $accessToken",
        ]);

        $authResponse->assertOk();
        $authResponse->assertJson(['data' => $user->toArray()]);
    }
}
