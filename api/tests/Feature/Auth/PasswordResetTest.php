<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase {
    use RefreshDatabase;

    public function testSendResetPasswordLinkSuccess() {
        Notification::fake();

        $user = $this->createUser();

        $response = $this->postJson('/v1/auth/reset-password/send', [
            'email' => $user->email,
        ]);

        $response->assertNoContent();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testResetPasswordSucceedsWithSentToken() {
        Notification::fake();

        $user = $this->createUser();

        $this->postJson('/v1/auth/reset-password/send', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
        ) use ($user) {
            $newPassword = self::DEFAULT_USER_PASSWORD . '--new';

            $response = $this->postJson('/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

            $response->assertNoContent();

            $user->refresh();
            $this->assertTrue(Hash::check($newPassword, $user->password));

            return true;
        });
    }

    public function testResetPasswordSucceedsWithoutSendingEmailIfUserDoesNotExist() {
        Notification::fake();

        $response = $this->postJson('/v1/auth/reset-password/send', [
            'email' => 'email-not-exists@example.com',
        ]);

        $response->assertNoContent();

        Notification::assertNothingSent();
    }

    public function testResetPasswordFailsWithInvalidToken() {
        $user = $this->createUser();

        $response = $this->postJson('/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertForbidden();
    }

    public function testResetPasswordFailsWithValidationError() {
        Notification::fake();

        $user = $this->createUser();

        $this->postJson('/v1/auth/reset-password/send', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
        ) use ($user) {
            $response = $this->postJson('/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'week',
                'password_confirmation' => 'week',
            ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrorFor('password');

            return true;
        });
    }

    public function testResetPasswordFailsWithWrongEmail() {
        $response = $this->postJson('/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'no_user@email.com',
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertForbidden();
    }
}

