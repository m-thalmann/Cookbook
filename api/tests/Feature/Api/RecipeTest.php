<?php

namespace Tests\Feature\Api;

use App\Models\Cookbook;
use App\Models\Recipe;
use Tests\TestCase;

class RecipeTest extends TestCase {
    public function testIndexReturnsAllRecipesForAUser() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $userRecipes = Recipe::factory(5)->create([
            'user_id' => $user->id,
        ]);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user);

        // not included
        $cookbookRecipes = Recipe::factory(5)->create([
            'cookbook_id' => $cookbook->id,
        ]);

        // not included
        $otherRecipes = Recipe::factory(5)->create(['is_public' => true]);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertOk();

        $this->assertJsonPagination(
            [
                'id',
                'user_id',
                'cookbook_id',
                'is_public',
                'name',
                'description',
                'category',
                'portions',
                'difficulty',
                'preparation',
                'preparation_time_minutes',
                'resting_time_minutes',
                'cooking_time_minutes',
                'deleted_at',
                'created_at',
                'updated_at',
                'user' => ['id', 'name', 'email', 'language_code'],
            ],
            count($userRecipes),
            $response
        );
    }

    public function testIndexReturnsAllRecipesAUserCanSee() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $userRecipes = Recipe::factory(5)->create([
            'user_id' => $user->id,
        ]);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user);

        $cookbookRecipes = Recipe::factory(5)->create([
            'cookbook_id' => $cookbook->id,
        ]);

        $publicRecipes = Recipe::factory(5)->create(['is_public' => true]);

        $privateRecipes = Recipe::factory(5)->create(['is_public' => false]);

        $response = $this->getJson('/api/v1/recipes?all');

        $response->assertOk();

        $this->assertEquals(
            count($userRecipes) +
                count($cookbookRecipes) +
                count($publicRecipes),
            $response->json('meta.total')
        );
    }

    public function testIndexReturnsAllExistingRecipesForAnAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipes = Recipe::factory(10)->create(['is_public' => false]);

        $response = $this->getJson('/api/v1/recipes?all');

        $response->assertOk();

        $this->assertEquals(count($recipes), $response->json('meta.total'));
    }

    public function testIndexReturnsAllPublicRecipesIfUserNotLoggedIn() {
        $publicRecipes = Recipe::factory(10)->create(['is_public' => true]);
        $privateRecipes = Recipe::factory(10)->create(['is_public' => false]);

        $response = $this->getJson('/api/v1/recipes');

        $response->assertOk();

        $this->assertEquals(
            count($publicRecipes),
            $response->json('meta.total')
        );
    }

    public function testStoreCreatesANewRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->make();

        $response = $this->postJson('/api/v1/recipes', $recipe->toArray());

        $response->assertCreated();

        $this->assertDatabaseHas('recipes', [
            'id' => $response->json('data.id'),
            'user_id' => $user->id,
            'is_public' => $recipe->is_public,
            'name' => $recipe->name,
            'description' => $recipe->description,
            'category' => $recipe->category,
            'portions' => $recipe->portions,
            'difficulty' => $recipe->difficulty,
            'preparation' => $recipe->preparation,
            'preparation_time_minutes' => $recipe->preparation_time_minutes,
            'resting_time_minutes' => $recipe->resting_time_minutes,
            'cooking_time_minutes' => $recipe->cooking_time_minutes,
        ]);
    }

    public function testStoreCreateRecipeWithCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $recipe = Recipe::factory()->make([
            'cookbook_id' => $cookbook->id,
        ]);

        $response = $this->postJson('/api/v1/recipes', $recipe->toArray());

        $response->assertCreated();

        $this->assertDatabaseHas('recipes', [
            'id' => $response->json('data.id'),
            'cookbook_id' => $cookbook->id,
        ]);
    }

    public function testStoreFailsIfUserIsNotAdminOfCookbook() {
        $user = $this->createAndLoginUser(isAdmin: true); // Does not matter whether user is admin

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $recipe = Recipe::factory()->make([
            'cookbook_id' => $cookbook->id,
        ]);

        $response = $this->postJson('/api/v1/recipes', $recipe->toArray());

        $response->assertUnprocessable();
    }

    public function testStoreFailsIfUserIsNotPartOfCookbook() {
        $user = $this->createAndLoginUser(isAdmin: true); // Does not matter whether user is admin

        $cookbook = Cookbook::factory()->create();

        $recipe = Recipe::factory()->make([
            'cookbook_id' => $cookbook->id,
        ]);

        $response = $this->postJson('/api/v1/recipes', $recipe->toArray());

        $response->assertUnprocessable();
    }

    public function testStoreFailsIfCookbookDoesntExist() {
        $user = $this->createAndLoginUser(isAdmin: true); // Does not matter whether user is admin

        $recipe = Recipe::factory()->make([
            'cookbook_id' => 1,
        ]);

        $response = $this->postJson('/api/v1/recipes', $recipe->toArray());

        $response->assertUnprocessable();
    }

    public function testStoreCreatesRecipeAndIngredients() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->make();

        $ingredients = [
            [
                'name' => 'Ingredient 1',
                'amount' => 1,
                'unit' => 'g',
                'order_index' => 0,
            ],
            [
                'name' => 'Ingredient 2',
                'amount' => 2,
                'unit' => 'kg',
                'order_index' => 1,
            ],
        ];

        $response = $this->postJson(
            '/api/v1/recipes',
            array_merge($recipe->toArray(), ['ingredients' => $ingredients])
        );

        $response->assertCreated();

        $this->assertDatabaseHas('recipes', [
            'id' => $response->json('data.id'),
        ]);

        foreach ($ingredients as $ingredient) {
            $this->assertDatabaseHas(
                'ingredients',
                $ingredient + ['recipe_id' => $response->json('data.id')]
            );
        }
    }

    public function testStoreFailsWhenOneIngredientCreationFails() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->make();

        $ingredients = [
            [
                'name' => 'Ingredient 1',
                'amount' => 1,
                'unit' => 'g',
                'order_index' => 0,
            ],
            [
                'name' => 'Ingredient 2',
                'amount' => -2,
                'unit' => 'kg',
                'order_index' => 1,
            ],
        ];

        $response = $this->postJson(
            '/api/v1/recipes',
            array_merge($recipe->toArray(), ['ingredients' => $ingredients])
        );

        $response->assertUnprocessable();

        $this->assertDatabaseMissing('recipes', [
            'id' => $response->json('data.id'),
        ]);

        $this->assertDatabaseMissing('ingredients', [
            'recipe_id' => $response->json('data.id'),
        ]);
    }

    public function testShowReturnsAUsersRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'cookbook_id',
                'is_public',
                'name',
                'description',
                'category',
                'portions',
                'difficulty',
                'preparation',
                'preparation_time_minutes',
                'resting_time_minutes',
                'cooking_time_minutes',
                'deleted_at',
                'created_at',
                'updated_at',
                'user' => ['id', 'name', 'email', 'language_code'],
            ],
        ]);
    }

    public function testShowReturnsRecipeInUsersCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $recipe = Recipe::factory()->create([
            'cookbook_id' => $cookbook->id,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertOk();
    }

    public function testShowReturnsPublicRecipe() {
        $recipe = Recipe::factory()->create([
            'is_public' => true,
        ]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertOk();
    }

    public function testShowFailsWhenRecipeIsNotViewableByUser() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertNotFound();
    }

    public function testShowSucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create([
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}");

        $response->assertOk();
    }

    public function testShowSharedReturnsSharedRecipe() {
        $uuid = 'test-uuid';

        $recipe = Recipe::factory()->create([
            'share_uuid' => $uuid,
            'is_public' => false,
        ]);

        $response = $this->getJson("/api/v1/recipes/shared/{$uuid}");

        $response->assertOk();
    }

    public function testShowSharedFailsIfShareUuidDoesntExist() {
        $response = $this->getJson('/api/v1/recipes/shared/doesnt-exist');

        $response->assertNotFound();
    }

    public function testUpdateUpdatesTheRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'user_id' => $user->id,
        ]);

        $newRecipe = Recipe::factory()->make(['user_id' => $recipe->id]);

        $response = $this->putJson(
            "/api/v1/recipes/{$recipe->id}",
            $newRecipe->toArray()
        );

        $response->assertOk();

        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->id,
            'user_id' => $user->id,
            'is_public' => $newRecipe->is_public,
            'name' => $newRecipe->name,
            'description' => $newRecipe->description,
            'category' => $newRecipe->category,
            'portions' => $newRecipe->portions,
            'difficulty' => $newRecipe->difficulty,
            'preparation' => $newRecipe->preparation,
            'preparation_time_minutes' => $newRecipe->preparation_time_minutes,
            'resting_time_minutes' => $newRecipe->resting_time_minutes,
            'cooking_time_minutes' => $newRecipe->cooking_time_minutes,
        ]);
    }

    public function testUpdateFailsIfUserIsNotAdminOfNewCookbookAndIsNotAdmin() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", [
            'cookbook_id' => $cookbook->id,
        ]);

        $response->assertUnprocessable();
    }

    public function testUpdateSucceedsIfUserIsAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $newRecipe = Recipe::factory()->make(['user_id' => $recipe->id]);

        $response = $this->putJson(
            "/api/v1/recipes/{$recipe->id}",
            $newRecipe->toArray()
        );

        $response->assertOk();
    }

    public function testUpdateFailsIfUserIsNotAdminOfRecipesCookbook() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => false]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $newRecipe = Recipe::factory()->make(['user_id' => $recipe->id]);

        $response = $this->putJson(
            "/api/v1/recipes/{$recipe->id}",
            $newRecipe->toArray()
        );

        $response->assertNotFound();
    }

    public function testUpdateFailsIfUserCantUpdateRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create();

        $newRecipe = Recipe::factory()->make(['user_id' => $recipe->id]);

        $response = $this->putJson(
            "/api/v1/recipes/{$recipe->id}",
            $newRecipe->toArray()
        );

        $response->assertNotFound();
    }

    public function testUpdateSucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create();

        $newRecipe = Recipe::factory()->make(['user_id' => $recipe->id]);

        $response = $this->putJson(
            "/api/v1/recipes/{$recipe->id}",
            $newRecipe->toArray()
        );

        $response->assertOk();
    }

    public function testUpdateSetsShareUuid() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", [
            'is_shared' => true,
        ]);

        $response->assertOk();

        $this->assertNotNull($response->json('data.share_uuid'));
    }

    public function testUpdateRemovesShareUuid() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'share_uuid' => 'test-uuid',
            'user_id' => $user->id,
        ]);

        $response = $this->putJson("/api/v1/recipes/{$recipe->id}", [
            'is_shared' => false,
        ]);

        $response->assertOk();

        $this->assertNull($response->json('data.share_uuid'));
    }

    public function testDestroyMovesRecipeToTrashForUser() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson("/api/v1/recipes/{$recipe->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('recipes', [
            'id' => $recipe->id,
        ]);
    }

    public function testDestroyFailsIfUserIsNotOwnerOrAdmin() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);

        $response = $this->deleteJson("/api/v1/recipes/{$recipe->id}");

        $response->assertNotFound();
    }

    public function testDestroySucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create();

        $response = $this->deleteJson("/api/v1/recipes/{$recipe->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('recipes', [
            'id' => $recipe->id,
        ]);
    }
}
