<?php

namespace Tests\Feature\Api\Auth;

use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use ReflectionClass;
use Tests\TestCase;

class PasswordResetTest extends TestCase {
    public function testSendResetPasswordLinkSuccess() {
        Notification::fake();

        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/reset-password/send', [
            'email' => $user->email,
        ]);

        $response->assertNoContent();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testSendResetPasswordLinkNotification() {
        $token = 'testToken';

        $user = $this->createUser();
        $notification = new ResetPassword($token);

        $notificationReflection = new ReflectionClass(ResetPassword::class);
        $resetUrlMethodAccessor = $notificationReflection->getMethod(
            'resetUrl'
        );
        $resetUrlMethodAccessor->setAccessible(true);

        $resetUrl = $resetUrlMethodAccessor->invokeArgs($notification, [$user]);

        $mail = $notification->toMail($user);

        $this->assertEquals(
            __('notifications.reset_password.subject'),
            $mail->subject
        );
        $this->assertEquals($resetUrl, $mail->actionUrl);
    }

    public function testResetPasswordSucceedsWithSentToken() {
        Notification::fake();

        $user = $this->createUser();

        $this->postJson('/api/v1/auth/reset-password/send', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
        ) use ($user) {
            $newPassword = self::DEFAULT_USER_PASSWORD . '--new';

            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => $newPassword,
            ]);

            $response->assertNoContent();

            $user->refresh();
            $this->assertTrue(Hash::check($newPassword, $user->password));

            return true;
        });
    }

    public function testResetPasswordSucceedsWithoutSendingEmailIfUserDoesNotExist() {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/reset-password/send', [
            'email' => 'email-not-exists@example.com',
        ]);

        $response->assertNoContent();

        Notification::assertNothingSent();
    }

    public function testResetPasswordFailsWithInvalidToken() {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertForbidden();
    }

    public function testResetPasswordFailsWithValidationError() {
        Notification::fake();

        $user = $this->createUser();

        $this->postJson('/api/v1/auth/reset-password/send', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo($user, ResetPassword::class, function (
            $notification
        ) use ($user) {
            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'weak',
            ]);

            $response->assertUnprocessable();
            $response->assertJsonValidationErrorFor('password');

            return true;
        });
    }

    public function testResetPasswordFailsWithWrongEmail() {
        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'no_user@email.com',
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertForbidden();
    }
}
