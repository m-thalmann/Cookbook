<?php

namespace Tests\Feature\Api\Auth;

use App\Models\Cookbook;
use App\Models\Recipe;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecipeTrashTest extends TestCase {
    public function testIndexReturnsAllTrashedRecipesForAUser() {
        $user = $this->createAndLoginUser(isAdmin: true); // does not matter whether is admin

        $deletedRecipes = Recipe::factory(2)->create(['user_id' => $user->id]);
        $deletedRecipes->each(fn($recipe) => $recipe->delete());

        // not included recipes

        $notDeletedRecipes = Recipe::factory(10)->create([
            'user_id' => $user->id,
        ]);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $cookbookRecipes = Recipe::factory(5)->create([
            'cookbook_id' => $cookbook->id,
        ]);
        $cookbookRecipes->each(fn($recipe) => $recipe->delete());

        $otherRecipes = Recipe::factory(5)->create();
        $otherRecipes->each(fn($recipe) => $recipe->delete());

        $response = $this->getJson('/v1/recipe-trash');

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
                'user_can_edit',
            ],
            count($deletedRecipes),
            $response
        );
    }

    public function testUpdateRestoresATrashedRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);
        $recipe->delete();

        $response = $this->putJson("/v1/recipe-trash/{$recipe->id}");

        $response->assertNoContent();

        $this->assertNull($recipe->fresh()->deleted_at);
    }

    public function testUpdateFailsIfUserIsNotOwnerOfRecipeAndNotAdmin() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]); // does not matter whether is admin

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);
        $recipe->delete();

        $response = $this->putJson("/v1/recipe-trash/{$recipe->id}");

        $response->assertNotFound();

        $this->assertNotNull($recipe->fresh()->deleted_at);
    }

    public function testUpdateSucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create();
        $recipe->delete();

        $response = $this->putJson("/v1/recipe-trash/{$recipe->id}");

        $response->assertNoContent();

        $this->assertNull($recipe->fresh()->deleted_at);
    }

    public function testUpdateFailsIfRecipeDoesntExist() {
        $user = $this->createAndLoginUser(isAdmin: true); // does not matter whether is admin

        $response = $this->putJson('/v1/recipe-trash/1');

        $response->assertNotFound();
    }

    public function testDestroyPermanentlyDeletesATrashedRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);
        $recipe->delete();

        $response = $this->deleteJson("/v1/recipe-trash/{$recipe->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function testDestroyFailsIfUserIsNotOwnerOfRecipeAndNotAdmin() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]); // does not matter whether is admin

        $recipe = Recipe::factory()->create(['cookbook_id' => $cookbook->id]);
        $recipe->delete();

        $response = $this->deleteJson("/v1/recipe-trash/{$recipe->id}");

        $response->assertNotFound();

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id]);
    }

    public function testDestroySucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create();
        $recipe->delete();

        $response = $this->deleteJson("/v1/recipe-trash/{$recipe->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function testDestroyFailsIfRecipeDoesntExist() {
        $user = $this->createAndLoginUser(isAdmin: true); // does not matter whether is admin

        $response = $this->deleteJson('/v1/recipe-trash/1');

        $response->assertNotFound();
    }

    public function testDestroyDeletesRecipeImages() {
        $user = $this->createAndLoginUser(isAdmin: false);

        /**
         * @var FilesystemAdapter
         */
        $storageFake = Storage::fake('public');

        $recipe = Recipe::factory()->create(['user_id' => $user->id]);
        $image = $recipe->images()->make();
        $image->image_path = 'dummy-image-path/image.jpg';
        $storageFake->put($image->image_path, 'dummy-image-content');
        $image->save();

        $recipe->delete();

        $response = $this->deleteJson("/v1/recipe-trash/{$recipe->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('recipe_images', ['id' => $image->id]);
        $storageFake->assertMissing($image->image_path);
    }

    public function testTruncatePermanentlyDeletesAllRecipesForAUser() {
        $user = $this->createAndLoginUser(isAdmin: true); // does not matter whether is admin

        $recipes = Recipe::factory(5)->create(['user_id' => $user->id]);
        $recipes->each(fn($recipe) => $recipe->delete());

        // not included recipes

        $notDeletedRecipes = Recipe::factory(10)->create([
            'user_id' => $user->id,
        ]);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user, ['is_admin' => true]);

        $cookbookRecipes = Recipe::factory(5)->create([
            'cookbook_id' => $cookbook->id,
        ]);
        $cookbookRecipes->each(fn($recipe) => $recipe->delete());

        $otherRecipes = Recipe::factory(5)->create();
        $otherRecipes->each(fn($recipe) => $recipe->delete());

        $response = $this->deleteJson('/v1/recipe-trash');

        $response->assertNoContent();

        $recipes->each(
            fn($recipe) => $this->assertDatabaseMissing('recipes', [
                'id' => $recipe->id,
            ])
        );

        collect([
            ...$notDeletedRecipes,
            ...$cookbookRecipes,
            ...$otherRecipes,
        ])->each(
            fn($recipe) => $this->assertDatabaseHas('recipes', [
                'id' => $recipe->id,
            ])
        );
    }

    public function testTruncateDeletesAllRecipeImagesForDeletedRecipes() {
        $user = $this->createAndLoginUser(isAdmin: false);

        /**
         * @var FilesystemAdapter
         */
        $storageFake = Storage::fake('public');

        $images = collect();

        $recipes = Recipe::factory(5)->create(['user_id' => $user->id]);
        $recipes->each(function ($recipe, $i) use ($storageFake, $images) {
            $image = $recipe->images()->make();
            $image->image_path = "dummy-image-path/image_{$i}.jpg";
            $storageFake->put($image->image_path, 'dummy-image-content');
            $image->save();
            $images->push($image);

            $recipe->delete();
        });

        $response = $this->deleteJson('/v1/recipe-trash');

        $response->assertNoContent();

        $images->each(function ($image) use ($storageFake) {
            $this->assertDatabaseMissing('recipe_images', ['id' => $image->id]);
            $storageFake->assertMissing($image->image_path);
        });
    }
}
