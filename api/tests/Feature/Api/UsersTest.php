<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use TokenAuth\TokenAuth;

class UsersTest extends TestCase {
    use RefreshDatabase, WithFaker;

    public function testShowAllUsersSucceedsForAdmin() {
        $amountUsers = 10;

        User::factory($amountUsers)->create();

        $this->createAndLoginUser(isAdmin: true);
        $amountUsers++;

        $response = $this->getJson('/v1/users');

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'first_name',
                'last_name',
                'email',
                'language_code',
                'is_admin',
                'email_verified_at',
                'created_at',
                'updated_at',
            ],
            $amountUsers,
            $response
        );
    }

    public function testShowAllUsersFailsForNonAdmin() {
        $this->createAndLoginUser(isAdmin: false);

        $response = $this->getJson('/v1/users');

        $response->assertForbidden();
    }

    public function testAdminCanCreateUser() {
        $this->createAndLoginUser(isAdmin: true);

        Notification::fake();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/users', $userData);

        $response->assertCreated();
        $response->assertJson([
            'data' => Arr::except($userData, [
                'password',
                'password_confirmation',
            ]),
        ]);

        Notification::assertSentTo(
            User::findOrFail($response->json('data.id')),
            VerifyEmail::class
        );
    }

    public function testAdminCanCreateAdminUser() {
        $this->createAndLoginUser(isAdmin: true);

        Notification::fake();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
            'is_admin' => true,
        ];

        $response = $this->postJson('/v1/users', $userData);

        $response->assertCreated();

        $user = User::findOrFail($response->json('data.id'));

        $this->assertEquals(true, $user->is_admin);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testAdminCanCreateUserWithVerifiedEmail() {
        $this->createAndLoginUser(isAdmin: true);

        Notification::fake();

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
            'is_verified' => true,
        ];

        $response = $this->postJson('/v1/users', $userData);

        $response->assertCreated();

        $user = User::findOrFail($response->json('data.id'));

        $this->assertTrue($user->hasVerifiedEmail());

        Notification::assertNothingSent();
    }

    public function testNonAdminCantCreateUser() {
        $this->createAndLoginUser(isAdmin: false);

        $userData = [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/users', $userData);

        $response->assertForbidden();
    }

    public function testCreateUserFailsWithValidationErrors() {
        $this->createAndLoginUser(isAdmin: true);

        $userData = [
            'first_name' => '',
            'email' => 'no_email',
            'password' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/v1/users', $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'email',
            'password',
        ]);
    }

    public function testUserCanViewItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->getJson("/v1/users/{$user->id}");

        $response->assertOk();

        $response->assertJson(['data' => $user->toArray()]);
    }

    public function testAdminUserCanViewOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser();

        $response = $this->getJson("/v1/users/{$otherUser->id}");

        $response->assertOk();

        $response->assertJson(['data' => $otherUser->toArray()]);
    }

    public function testNonAdminCantViewOtherUser() {
        $this->createAndLoginUser(isAdmin: false);
        $otherUser = $this->createUser();

        $response = $this->getJson("/v1/users/{$otherUser->id}");

        $response->assertNotFound();
    }

    public function testUserCanUpdateItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $newFirstName = "new_{$user->first_name}";

        $response = $this->putJson("/v1/users/{$user->id}", [
            'first_name' => $newFirstName,
        ]);

        $response->assertOk();

        $user->refresh();

        $this->assertEquals($newFirstName, $user->first_name);

        $response->assertJson(['data' => $user->toArray()]);
    }

    public function testUserCanUpdateAndLogout() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken');

        $newFirstName = "new_{$user->first_name}";

        $response = $this->putJson("/v1/users/{$user->id}", [
            'first_name' => $newFirstName,
            'do_logout' => true,
        ]);

        $response->assertOk();

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function testUserRequiresCurrentPasswordIfEmailIsUpdatedForItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/v1/users/{$user->id}", [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrorFor('current_password');
    }

    public function testUserRequiresCurrentPasswordIfPasswordIsUpdatedForItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/v1/users/{$user->id}", [
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrorFor('current_password');
    }

    public function testUserIsLoggedOutIfEmailIsUpdated() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken');

        $newEmail = "new_{$user->email}";

        $response = $this->putJson("/v1/users/{$user->id}", [
            'current_password' => self::DEFAULT_USER_PASSWORD,
            'email' => $newEmail,
        ]);

        $response->assertOk();

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function testUserIsLoggedOutIfPasswordIsUpdated() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken');

        $newPassword = 'new_' . self::DEFAULT_USER_PASSWORD;

        $response = $this->putJson("/v1/users/{$user->id}", [
            'current_password' => self::DEFAULT_USER_PASSWORD,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertOk();

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function testUserPasswordIsNotUpdatedIfIsSame() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenAuth::TYPE_ACCESS, 'TestToken');

        $oldPassword = $user->password;

        $response = $this->putJson("/v1/users/{$user->id}", [
            'current_password' => self::DEFAULT_USER_PASSWORD,
            'password' => self::DEFAULT_USER_PASSWORD,
            'password_confirmation' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertOk();

        $user->refresh();

        $this->assertEquals($oldPassword, $user->password);

        // user is not logged out since credentials did not change
        $this->assertGreaterThanOrEqual(1, $user->tokens()->count());
    }

    public function testAdminUserCanUpdateOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser();

        $newFirstName = "new_{$otherUser->first_name}";

        $response = $this->putJson("/v1/users/{$otherUser->id}", [
            'first_name' => $newFirstName,
        ]);

        $response->assertOk();

        $otherUser->refresh();

        $this->assertEquals($newFirstName, $otherUser->first_name);

        $response->assertJson(['data' => $otherUser->toArray()]);
    }

    public function testAdminCanUpdateAdminRoleForOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser(isAdmin: false);

        $response = $this->putJson("/v1/users/{$otherUser->id}", [
            'is_admin' => true,
        ]);

        $response->assertOk();

        $otherUser->refresh();

        $this->assertTrue($otherUser->is_admin);
    }

    public function testAdminCanSetEmailAsUnverifiedForOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser(isEmailVerified: true);

        $this->assertTrue($otherUser->hasVerifiedEmail());

        $response = $this->putJson("/v1/users/{$otherUser->id}", [
            'is_verified' => false,
        ]);

        $response->assertOk();

        $otherUser->refresh();

        $this->assertFalse($otherUser->hasVerifiedEmail());
    }

    public function testAdminCanSetEmailAsVerifiedForOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser(isEmailVerified: false);

        $this->assertFalse($otherUser->hasVerifiedEmail());

        $response = $this->putJson("/v1/users/{$otherUser->id}", [
            'is_verified' => true,
        ]);

        $response->assertOk();

        $otherUser->refresh();

        $this->assertTrue($otherUser->hasVerifiedEmail());
    }

    public function testNonAdminCantUpdateOtherUser() {
        $this->createAndLoginUser(isAdmin: false);
        $otherUser = $this->createUser();

        $response = $this->putJson("/v1/users/{$otherUser->id}", [
            'first_name' => 'John',
        ]);

        $response->assertNotFound();
    }

    public function testNonAdminCantUpdateOwnAdminRole() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/v1/users/{$user->id}", [
            'is_admin' => true,
        ]);

        $response->assertForbidden();
    }

    public function testNonAdminCantUpdateOwnEmailVerificationState() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/v1/users/{$user->id}", [
            'is_verified' => false,
        ]);

        $response->assertForbidden();
    }

    public function testAdminCantUpdateOwnAdminRole() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/v1/users/{$user->id}", [
            'is_admin' => true,
        ]);

        $response->assertForbidden();
    }

    public function testAdminCantUpdateOwnEmailVerificationState() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/v1/users/{$user->id}", [
            'is_verified' => false,
        ]);

        $response->assertForbidden();
    }

    public function testUserUpdateFailsWithValidationErrors() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $userData = [
            'first_name' => '',
            'email' => 'no_email',
            'password' => '123',
        ];

        $response = $this->putJson("/v1/users/{$user->id}", $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'first_name',
            'email',
            'password',
        ]);
    }

    public function testUserCanDeleteItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->deleteJson("/v1/users/{$user->id}");

        $response->assertNoContent();

        $this->assertNull($user->fresh());
    }

    public function testAdminUserCanDeleteOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser();

        $response = $this->deleteJson("/v1/users/{$otherUser->id}");

        $response->assertNoContent();

        $this->assertNull($otherUser->fresh());
    }

    public function testNonAdminUserCantDeleteOtherUser() {
        $this->createAndLoginUser(isAdmin: false);
        $otherUser = $this->createUser();

        $response = $this->deleteJson("/v1/users/{$otherUser->id}");

        $response->assertNotFound();
    }
}
