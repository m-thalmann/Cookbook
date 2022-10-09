<?php

namespace Tests\Feature\Api\Auth;

use App\Models\AuthToken;
use App\Models\User;
use App\Services\HCaptchaService;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SignUpTest extends TestCase {
    use WithFaker;

    public function testUserSignUpSucceeds() {
        Notification::fake();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/auth/sign-up', $userData);

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'user' => Arr::except($userData, ['password']),
            ],
        ]);
        $response->assertJsonStructure([
            'data' => [
                'user' => ['name', 'email', 'id'],
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

    public function testUserSignUpSucceedsWithValidHCaptchaToken() {
        Config::set('services.hcaptcha.enabled', true);

        Notification::fake();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'hcaptcha_token' => HCaptchaService::VALID_TEST_TOKEN,
        ];

        $response = $this->postJson('/v1/auth/sign-up', $userData);

        $response->assertCreated();

        Notification::assertSentTo(
            User::findOrFail($response->json('data.user.id')),
            VerifyEmail::class
        );
    }

    public function testUserSignUpFailsWithValidationErrors() {
        Notification::fake();

        $password = 'insecure';

        $userData = [
            'password' => $password,
        ];

        $response = $this->postJson('/v1/auth/sign-up', $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'email', 'password']);

        Notification::assertNothingSent();
    }

    public function testUserSignUpFailsWhenEmailIsNotUnique() {
        Notification::fake();

        $user = $this->createUser();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/auth/sign-up', $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrorFor('email');

        Notification::assertNothingSent();
    }

    public function testUserSignUpFailsWithInvalidHCaptchaToken() {
        Config::set('services.hcaptcha.enabled', true);

        Notification::fake();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'hcaptcha_token' => HCaptchaService::INVALID_TEST_TOKEN,
        ];

        $response = $this->postJson('/v1/auth/sign-up', $userData);

        $response->assertForbidden();

        Notification::assertNothingSent();
    }

    public function testUserSignUpFailsWhenIsDisabled() {
        Config::set('app.sign_up_enabled', false);

        Notification::fake();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/auth/sign-up', $userData);

        $response->assertStatus(405);

        Notification::assertNothingSent();
    }
}

