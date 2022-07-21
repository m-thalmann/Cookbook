<?php

namespace Tests\Feature\Auth\Api;

use App\Models\AuthToken;
use App\Models\User;
use App\Services\HCaptchaService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterTest extends TestCase {
    use WithFaker;

    public function testUserRegistrationSucceeds() {
        Notification::fake();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/auth/register', $userData);

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'user' => Arr::except($userData, [
                    'password',
                    'password_confirmation',
                ]),
            ],
        ]);
        $response->assertJsonStructure([
            'data' => [
                'user' => ['first_name', 'last_name', 'email', 'id'],
                'access_token',
                'refresh_token',
            ],
        ]);

        $this->assertNotNull(
            AuthToken::findAccessToken($response->json('data.access_token'))
        );
        $this->assertNotNull(
            AuthToken::findRefreshToken($response->json('data.refresh_token'))
        );

        Notification::assertSentTo(
            User::findOrFail($response->json('data.user.id')),
            VerifyEmail::class
        );
    }

    public function testUserRegistrationSucceedsWithValidHCaptchaToken() {
        Config::set('services.hcaptcha.enabled', true);

        Notification::fake();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
            'hcaptcha_token' => HCaptchaService::VALID_TEST_TOKEN,
        ];

        $response = $this->postJson('/v1/auth/register', $userData);

        $response->assertCreated();

        Notification::assertSentTo(
            User::findOrFail($response->json('data.user.id')),
            VerifyEmail::class
        );
    }

    public function testUserRegistrationFailsWithValidationErrors() {
        Notification::fake();

        $password = 'insecure';

        $userData = [
            'password' => $password,
            'password_confirmation' => 'not-correct',
        ];

        $response = $this->postJson('/v1/auth/register', $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'email',
            'password',
        ]);

        Notification::assertNothingSent();
    }

    public function testUserRegistrationFailsWhenEmailIsNotUnique() {
        Notification::fake();

        $user = $this->createUser();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/auth/register', $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrorFor('email');

        Notification::assertNothingSent();
    }

    public function testUserRegistrationFailsWithInvalidHCaptchaToken() {
        Config::set('services.hcaptcha.enabled', true);

        Notification::fake();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
            'hcaptcha_token' => HCaptchaService::INVALID_TEST_TOKEN,
        ];

        $response = $this->postJson('/v1/auth/register', $userData);

        $response->assertForbidden();

        Notification::assertNothingSent();
    }

    public function testUserRegistrationFailsWhenIsDisabled() {
        Config::set('app.registration_enabled', false);

        Notification::fake();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/auth/register', $userData);

        $response->assertStatus(405);

        Notification::assertNothingSent();
    }
}

