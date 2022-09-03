<?php

namespace Tests\Feature\Api\Auth;

use App\Exceptions\UnauthorizedHttpException;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use ReflectionClass;
use Tests\TestCase;
use TokenAuth\TokenAuth;

class EmailVerificationTest extends TestCase {
    public function testSucceedsWithValidHashAndSignature() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        TokenAuth::actingAs($user);

        Event::fake();

        $response = $this->postJson(
            $this->getVerificationPath($user->id, $user->email)
        );

        $response->assertNoContent();

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function testSentNotificationIsCorrect() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $notification = new VerifyEmail();

        $notificationReflection = new ReflectionClass(VerifyEmail::class);
        $verificationUrlMethodAccessor = $notificationReflection->getMethod(
            'verificationUrl'
        );
        $verificationUrlMethodAccessor->setAccessible(true);

        $verificationUrl = $verificationUrlMethodAccessor->invokeArgs(
            $notification,
            [$user]
        );

        $mail = $notification->toMail($user);

        $this->assertEquals(
            __('notifications.verify_email.subject'),
            $mail->subject
        );
        $this->assertEquals($verificationUrl, $mail->actionUrl);
    }

    public function testFailsWhenIsDisabled() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        TokenAuth::actingAs($user);

        Event::fake();

        Config::set('app.email_verification_enabled', false);

        $response = $this->postJson(
            $this->getVerificationPath($user->id, $user->email)
        );

        $response->assertForbidden();

        Config::set('app.email_verification_enabled', true);

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function testFailsWithInvalidHashAndValidSignature() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        TokenAuth::actingAs($user);

        Event::fake();

        $response = $this->postJson(
            $this->getVerificationPath($user->id, 'wrong-email')
        );

        $response->assertForbidden();

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function testFailsForOtherUser() {
        $user = User::factory()->create();

        TokenAuth::actingAs($user);

        $otherUser = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $response = $this->postJson(
            $this->getVerificationPath($otherUser->id, $otherUser->email)
        );

        $response->assertForbidden();

        Event::assertNotDispatched(Verified::class);
        $this->assertFalse($otherUser->fresh()->hasVerifiedEmail());
    }

    public function testFailsWhenUnauthorized() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        Event::fake();

        $response = $this->postJson(
            $this->getVerificationPath($user->id, 'wrong-email')
        );
        $response->assertUnauthorized();

        Event::assertNotDispatched(Verified::class);
    }

    public function testResendEmailSucceedsForAuthUser() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        TokenAuth::actingAs($user);

        Notification::fake();

        $response = $this->postJson('/v1/auth/email-verification/resend');

        $response->assertNoContent();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testResendEmailFailsWhenUnauthorized() {
        Notification::fake();

        $response = $this->postJson('/v1/auth/email-verification/resend');
        $response->assertUnauthorized();

        Notification::assertNothingSent();
    }

    public function testResendEmailFailsWhenIsDisabled() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        TokenAuth::actingAs($user);

        Notification::fake();

        Config::set('app.email_verification_enabled', false);

        $response = $this->postJson('/v1/auth/email-verification/resend');

        $response->assertForbidden();

        Notification::assertNothingSent();
    }

    public function testEnsureEmailIsVerifiedMiddlewareReturnsUnauthorizedWhenNotVerified() {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        TokenAuth::actingAs($user);

        $request = new Request();

        $middleware = new EnsureEmailIsVerified();

        $this->assertThrows(
            fn() => $middleware->handle($request, fn() => null),
            UnauthorizedHttpException::class,
            expectedMessage: __('messages.email_must_be_verified')
        );
    }

    private function getVerificationPath($userId, $email) {
        $verificationUrl = URL::temporarySignedRoute(
            'api.v1.auth.email_verification.verify',
            now()->addMinutes(60),
            ['id' => $userId, 'hash' => sha1($email)]
        );

        $parsedUrl = parse_url($verificationUrl);
        return "{$parsedUrl['path']}?{$parsedUrl['query']}";
    }
}
