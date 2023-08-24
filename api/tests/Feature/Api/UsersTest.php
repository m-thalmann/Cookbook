<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use TokenAuth\Enums\TokenType;

class UsersTest extends TestCase {
    use WithFaker;

    public function testShowAllUsersSucceedsForAdmin() {
        $amountUsers = 10;

        User::factory($amountUsers)->create();

        $this->createAndLoginUser(isAdmin: true);
        $amountUsers++;

        $response = $this->getJson('/api/v1/users');

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'name',
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

        $response = $this->getJson('/api/v1/users');

        $response->assertForbidden();
    }

    public function testAdminCanCreateUser() {
        $this->createAndLoginUser(isAdmin: true);

        Notification::fake();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertCreated();
        $response->assertJson([
            'data' => Arr::except($userData, ['password']),
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
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'is_admin' => true,
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertCreated();

        $user = User::findOrFail($response->json('data.id'));

        $this->assertEquals(true, $user->is_admin);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testAdminCanCreateUserWithVerifiedEmail() {
        $this->createAndLoginUser(isAdmin: true);

        Notification::fake();

        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
            'is_verified' => true,
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertCreated();

        $user = User::findOrFail($response->json('data.id'));

        $this->assertTrue($user->hasVerifiedEmail());

        Notification::assertNothingSent();
    }

    public function testNonAdminCantCreateUser() {
        $this->createAndLoginUser(isAdmin: false);

        $userData = [
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertForbidden();
    }

    public function testCreateUserFailsWithValidationErrors() {
        $this->createAndLoginUser(isAdmin: true);

        $userData = [
            'name' => '',
            'email' => 'no_email',
            'password' => self::DEFAULT_USER_PASSWORD,
        ];

        $response = $this->postJson('/api/v1/users', $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'email']);
    }

    public function testUserCanViewItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->getJson("/api/v1/users/{$user->id}");

        $response->assertOk();

        $response->assertJson(['data' => $user->toArray()]);
    }

    public function testAdminUserCanViewOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser();

        $response = $this->getJson("/api/v1/users/{$otherUser->id}");

        $response->assertOk();

        $response->assertJson(['data' => $otherUser->toArray()]);
    }

    public function testNonAdminCantViewOtherUser() {
        $this->createAndLoginUser(isAdmin: false);
        $otherUser = $this->createUser();

        $response = $this->getJson("/api/v1/users/{$otherUser->id}");

        $response->assertNotFound();
    }

    public function testUserCanBeSearchedByEmail() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->getJson("/api/v1/users/search/email/{$user->email}");

        $response->assertOk();

        $response->assertJson(['data' => $user->toArray()]);
    }

    public function testUserCanUpdateItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $newName = "new_{$user->name}";

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $newName,
        ]);

        $response->assertOk();

        $user->refresh();

        $this->assertEquals($newName, $user->name);

        $response->assertJson(['data' => $user->toArray()]);
    }

    public function testUserCanUpdateAndLogout() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenType::ACCESS)->build();

        $newName = "new_{$user->name}";

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'name' => $newName,
            'do_logout' => true,
        ]);

        $response->assertOk();

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function testUserRequiresCurrentPasswordIfEmailIsUpdatedForItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrorFor('current_password');
    }

    public function testUserRequiresCurrentPasswordIfPasswordIsUpdatedForItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'password' => self::DEFAULT_USER_PASSWORD,
        ]);

        $response->assertUnprocessable();

        $response->assertJsonValidationErrorFor('current_password');
    }

    public function testUserIsLoggedOutIfEmailIsUpdated() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenType::ACCESS)->build();

        $newEmail = "new_{$user->email}";

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'current_password' => self::DEFAULT_USER_PASSWORD,
            'email' => $newEmail,
        ]);

        $response->assertOk();

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function testUserIsLoggedOutIfPasswordIsUpdated() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenType::ACCESS)->build();

        $newPassword = 'new_' . self::DEFAULT_USER_PASSWORD;

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'current_password' => self::DEFAULT_USER_PASSWORD,
            'password' => $newPassword,
        ]);

        $response->assertOk();

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function testUserPasswordIsNotUpdatedIfIsSame() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $user->createToken(TokenType::ACCESS)->build();

        $oldPassword = $user->password;

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'current_password' => self::DEFAULT_USER_PASSWORD,
            'password' => self::DEFAULT_USER_PASSWORD,
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

        $newName = "new_{$otherUser->name}";

        $response = $this->putJson("/api/v1/users/{$otherUser->id}", [
            'name' => $newName,
        ]);

        $response->assertOk();

        $otherUser->refresh();

        $this->assertEquals($newName, $otherUser->name);

        $response->assertJson(['data' => $otherUser->toArray()]);
    }

    public function testAdminCanUpdateAdminRoleForOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser(isAdmin: false);

        $response = $this->putJson("/api/v1/users/{$otherUser->id}", [
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

        $response = $this->putJson("/api/v1/users/{$otherUser->id}", [
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

        $response = $this->putJson("/api/v1/users/{$otherUser->id}", [
            'is_verified' => true,
        ]);

        $response->assertOk();

        $otherUser->refresh();

        $this->assertTrue($otherUser->hasVerifiedEmail());
    }

    public function testNonAdminCantUpdateOtherUser() {
        $this->createAndLoginUser(isAdmin: false);
        $otherUser = $this->createUser();

        $response = $this->putJson("/api/v1/users/{$otherUser->id}", [
            'name' => 'John Doe',
        ]);

        $response->assertNotFound();
    }

    public function testNonAdminCantUpdateOwnAdminRole() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'is_admin' => true,
        ]);

        $response->assertForbidden();
    }

    public function testNonAdminCantUpdateOwnEmailVerificationState() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'is_verified' => false,
        ]);

        $response->assertForbidden();
    }

    public function testAdminCantUpdateOwnAdminRole() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'is_admin' => true,
        ]);

        $response->assertForbidden();
    }

    public function testAdminCantUpdateOwnEmailVerificationState() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->putJson("/api/v1/users/{$user->id}", [
            'is_verified' => false,
        ]);

        $response->assertForbidden();
    }

    public function testUserUpdateFailsWithValidationErrors() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $userData = [
            'name' => '',
            'email' => 'no_email',
            'password' => '123',
        ];

        $response = $this->putJson("/api/v1/users/{$user->id}", $userData);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function testUserCanDeleteItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->deleteJson("/api/v1/users/{$user->id}");

        $response->assertNoContent();

        $this->assertNull($user->fresh());
    }

    public function testAdminUserCanDeleteOtherUser() {
        $this->createAndLoginUser(isAdmin: true);
        $otherUser = $this->createUser();

        $response = $this->deleteJson("/api/v1/users/{$otherUser->id}");

        $response->assertNoContent();

        $this->assertNull($otherUser->fresh());
    }

    public function testNonAdminUserCantDeleteOtherUser() {
        $this->createAndLoginUser(isAdmin: false);
        $otherUser = $this->createUser();

        $response = $this->deleteJson("/api/v1/users/{$otherUser->id}");

        $response->assertNotFound();
    }
}
