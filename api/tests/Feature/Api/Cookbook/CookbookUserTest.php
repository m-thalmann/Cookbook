<?php

namespace Tests\Feature\Api\Cookbook;

use App\Models\Cookbook;
use App\Models\User;
use Tests\TestCase;

class CookbookUserTest extends TestCase {
    public function testIndexReturnsTheUsersForAGivenCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $users = User::factory(5)->create();

        foreach ($users as $cookbookUser) {
            $cookbook->users()->attach($cookbookUser);
        }

        $response = $this->getJson("/api/v1/cookbooks/{$cookbook->id}/users");

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'name',
                'email',
                'language_code',
                'meta' => ['is_admin', 'created_at', 'updated_at'],
            ],
            count($users) + 1, // +1 for the authenticated user
            $response
        );
    }

    public function testIndexFailsWhenUserIsNotAdminOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $users = User::factory(5)->create();

        foreach ($users as $cookbookUser) {
            $cookbook->users()->attach($cookbookUser);
        }

        $response = $this->getJson("/api/v1/cookbooks/{$cookbook->id}/users");

        $response->assertNotFound();
    }

    public function testIndexFailsWhenUserIsNotPartOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $users = User::factory(5)->create();

        foreach ($users as $cookbookUser) {
            $cookbook->users()->attach($cookbookUser);
        }

        $response = $this->getJson("/api/v1/cookbooks/{$cookbook->id}/users");

        $response->assertNotFound();
    }

    public function testIndexSucceedsWhenUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $users = User::factory(5)->create();

        foreach ($users as $cookbookUser) {
            $cookbook->users()->attach($cookbookUser);
        }

        $response = $this->getJson("/api/v1/cookbooks/{$cookbook->id}/users");

        $response->assertOk();

        $this->assertEquals(count($users), $response->json('meta.total'));
    }

    public function testStoreAddsAUserToACookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $userToAdd = User::factory()->create();

        $response = $this->postJson("/api/v1/cookbooks/{$cookbook->id}/users", [
            'user_id' => $userToAdd->id,
            'is_admin' => false,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('cookbook_user', [
            'cookbook_id' => $cookbook->id,
            'user_id' => $userToAdd->id,
            'is_admin' => false,
        ]);
    }

    public function testStoreFailsWhenUserIsNotAdminOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $userToAdd = User::factory()->create();

        $response = $this->postJson("/api/v1/cookbooks/{$cookbook->id}/users", [
            'user_id' => $userToAdd->id,
            'is_admin' => false,
        ]);

        $response->assertNotFound();
    }

    public function testStoreFailsWhenUserIsNotPartOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $userToAdd = User::factory()->create();

        $response = $this->postJson("/api/v1/cookbooks/{$cookbook->id}/users", [
            'user_id' => $userToAdd->id,
            'is_admin' => false,
        ]);

        $response->assertNotFound();
    }

    public function testStoreSucceedsWhenUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $userToAdd = User::factory()->create();

        $response = $this->postJson("/api/v1/cookbooks/{$cookbook->id}/users", [
            'user_id' => $userToAdd->id,
            'is_admin' => false,
        ]);

        $response->assertCreated();
    }

    public function testStoreFailsWhenUserIsAlreadyInCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $userToAdd = User::factory()->create();

        $cookbook->users()->attach($userToAdd);

        $response = $this->postJson("/api/v1/cookbooks/{$cookbook->id}/users", [
            'user_id' => $userToAdd->id,
            'is_admin' => false,
        ]);

        $response->assertUnprocessable();
    }

    public function testUpdateUpdatesACookbookUser() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $userToUpdate = User::factory()->create();

        $cookbook->users()->attach($userToUpdate);

        $response = $this->putJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToUpdate->id}",
            [
                'is_admin' => true,
            ]
        );

        $response->assertOk();

        $this->assertDatabaseHas('cookbook_user', [
            'cookbook_id' => $cookbook->id,
            'user_id' => $userToUpdate->id,
            'is_admin' => true,
        ]);
    }

    public function testUpdateFailsWhenUserIsNotAdminOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $userToUpdate = User::factory()->create();

        $cookbook->users()->attach($userToUpdate);

        $response = $this->putJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToUpdate->id}",
            [
                'is_admin' => true,
            ]
        );

        $response->assertNotFound();
    }

    public function testUpdateFailsWhenUserIsNotPartOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $userToUpdate = User::factory()->create();

        $response = $this->putJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToUpdate->id}",
            [
                'is_admin' => true,
            ]
        );

        $response->assertNotFound();
    }

    public function testUpdateSucceedsWhenUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $userToUpdate = User::factory()->create();

        $cookbook->users()->attach($userToUpdate);

        $response = $this->putJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToUpdate->id}",
            [
                'is_admin' => true,
            ]
        );

        $response->assertOk();
    }

    public function testUpdateFailsWhenUserUpdatesItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $cookbook->users()->attach($user, ['is_admin' => true]);

        $response = $this->putJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$user->id}",
            [
                'is_admin' => false,
            ]
        );

        $response->assertForbidden();
    }

    public function testDestroyRemovesAUserFromACookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $userToRemove = User::factory()->create();

        $cookbook->users()->attach($userToRemove);

        $response = $this->deleteJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToRemove->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('cookbook_user', [
            'cookbook_id' => $cookbook->id,
            'user_id' => $userToRemove->id,
        ]);
    }

    public function testDestroyFailsWhenUserIsNotAdminOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $userToRemove = User::factory()->create();

        $cookbook->users()->attach($userToRemove);

        $response = $this->deleteJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToRemove->id}"
        );

        $response->assertNotFound();
    }

    public function testDestroyFailsWhenUserIsNotPartOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $userToRemove = User::factory()->create();

        $response = $this->deleteJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToRemove->id}"
        );

        $response->assertNotFound();
    }

    public function testDestroySucceedsWhenUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();
        $adminUser = User::factory()->create();
        $cookbook->users()->attach($adminUser, ['is_admin' => true]);

        $userToRemove = User::factory()->create();

        $cookbook->users()->attach($userToRemove);

        $response = $this->deleteJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$userToRemove->id}"
        );

        $response->assertNoContent();
    }

    public function testDestroyFailsWhenUserRemovesLastAdmin() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $cookbook->users()->attach($user, ['is_admin' => true]);

        $response = $this->deleteJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$user->id}"
        );

        $response->assertConflict();
    }

    public function testDestroySucceedsWhenUserRemovesItself() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $otherAdmin = User::factory()->create();
        $cookbook->users()->attach($otherAdmin, ['is_admin' => true]);

        $response = $this->deleteJson(
            "/api/v1/cookbooks/{$cookbook->id}/users/{$user->id}"
        );

        $response->assertNoContent();
    }
}
