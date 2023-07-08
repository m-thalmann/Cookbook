<?php

namespace Tests\Feature\Api\Cookbook;

use App\Models\Cookbook;
use Tests\TestCase;

class CookbookTest extends TestCase {
    public function testIndexReturnsAllCookbooksForAUser() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbooks = Cookbook::factory(5)->create();
        $cookbooks->each(function ($cookbook) use ($user) {
            $cookbook->users()->attach($user);
        });

        $notUserCookbooks = Cookbook::factory(10)->create();

        $response = $this->getJson('/api/v1/cookbooks');

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'name',
                'created_at',
                'updated_at',
                'recipes_count',
                'users_count',
            ],
            count($cookbooks),
            $response
        );
    }

    public function testIndexReturnsAllCookbooksIfIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbooks = Cookbook::factory(5)->create();

        $response = $this->getJson('/api/v1/cookbooks?all');

        $response->assertOk();

        $this->assertEquals(count($cookbooks), $response->json('meta.total'));
    }

    public function testIndexEditableReturnsAllCookbooksAUserIsAnAdminOf() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbooks = Cookbook::factory(5)->create();

        $amountAdmin = rand(1, count($cookbooks));

        for ($i = 0; $i < $amountAdmin; $i++) {
            $cookbooks[$i]->users()->attach($user, [
                'is_admin' => true,
            ]);
        }

        $response = $this->getJson('/api/v1/cookbooks/editable');

        $response->assertOk();

        $response->assertJsonStructure(['data' => [['id', 'name']]]);
        $this->assertEquals($amountAdmin, count($response->json('data')));
    }

    public function testIndexEditableReturnsAllCookbooksIfIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbooks = Cookbook::factory(5)->create();

        $response = $this->getJson('/api/v1/cookbooks/editable');

        $response->assertOk();

        $this->assertEquals(count($cookbooks), count($response->json('data')));
    }

    public function testStoreCreatesACookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->make();

        $response = $this->postJson('/api/v1/cookbooks', [
            'name' => $cookbook->name,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('cookbooks', [
            'name' => $cookbook->name,
        ]);
        $this->assertDatabaseHas('cookbook_user', [
            'user_id' => $user->id,
            'cookbook_id' => $response->json('data.id'),
            'is_admin' => true,
        ]);
    }

    public function testShowReturnsTheCookbookForAUser() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user);

        $response = $this->getJson("/api/v1/cookbooks/{$cookbook->id}");

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'meta' => ['is_admin', 'created_at', 'updated_at'],
            ],
        ]);
    }

    public function testShowFailsWhenUserIsNotPartOfTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();

        $response = $this->getJson("/api/v1/cookbooks/{$cookbook->id}");

        $response->assertNotFound();
    }

    public function testShowSucceedsWhenUserIsAnAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $response = $this->getJson("/api/v1/cookbooks/{$cookbook->id}");

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => ['id', 'name'],
        ]);
    }

    public function testUpdateUpdatesTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $newName = 'New Cookbook Name';

        $response = $this->putJson("/api/v1/cookbooks/{$cookbook->id}", [
            'name' => $newName,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('cookbooks', [
            'id' => $cookbook->id,
            'name' => $newName,
        ]);
    }

    public function testUpdateFailsWhenUserIsNotAdmin() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $newName = 'New Cookbook Name';

        $response = $this->putJson("/api/v1/cookbooks/{$cookbook->id}", [
            'name' => $newName,
        ]);

        $response->assertNotFound();
    }

    public function testUpdateSucceedsWhenUserIsAnAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $newName = 'New Cookbook Name';

        $response = $this->putJson("/api/v1/cookbooks/{$cookbook->id}", [
            'name' => $newName,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('cookbooks', [
            'id' => $cookbook->id,
            'name' => $newName,
        ]);
    }

    public function testDestroyDeletesTheCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $response = $this->deleteJson("/api/v1/cookbooks/{$cookbook->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('cookbooks', [
            'id' => $cookbook->id,
        ]);
    }

    public function testDestroyFailsWhenUserIsNotAdminOfCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $response = $this->deleteJson("/api/v1/cookbooks/{$cookbook->id}");

        $response->assertNotFound();
    }

    public function testDestroySucceedsWhenUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $cookbook = Cookbook::factory()->create();

        $response = $this->deleteJson("/api/v1/cookbooks/{$cookbook->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('cookbooks', [
            'id' => $cookbook->id,
        ]);
    }
}
