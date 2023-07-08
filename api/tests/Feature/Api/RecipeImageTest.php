<?php

namespace Tests\Feature\Api;

use App\Models\Recipe;
use App\Models\RecipeImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecipeImageTest extends TestCase {
    private FilesystemAdapter $storageFake;

    public function setUp(): void {
        parent::setUp();

        $this->storageFake = Storage::fake('public');
    }

    private function createImagesForRecipe(Recipe $recipe, int $amount = 5) {
        $images = collect([]);

        for ($i = 0; $i < $amount; $i++) {
            $image = $recipe->images()->make();
            $image->image_path = "dummy-image-path/image_{$i}.jpg";
            $this->storageFake->put($image->image_path, 'dummy-image-content');
            $image->save();

            $images->push($image);
        }

        return $images;
    }

    public function testIndexReturnsAllImagesForARecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $images = $this->createImagesForRecipe($recipe);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}/images");

        $response->assertOk();

        $response->assertJsonCount(count($images), 'data');

        $response->assertJsonStructure([
            'data' => [['id', 'url']],
        ]);
    }

    public function testIndexFailsWhenRecipeDoesntExist() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $response = $this->getJson('/api/v1/recipes/1/images');

        $response->assertNotFound();
    }

    public function testIndexFailsWhenUserCantViewRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['is_public' => false]);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}/images");

        $response->assertNotFound();
    }

    public function testIndexSucceedsIfUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create(['is_public' => false]);

        $images = $this->createImagesForRecipe($recipe);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}/images");

        $response->assertOk();

        $response->assertJsonCount(count($images), 'data');
    }

    public function testIndexSucceedsIfRecipeIsPublic() {
        $recipe = Recipe::factory()->create(['is_public' => true]);

        $images = $this->createImagesForRecipe($recipe);

        $response = $this->getJson("/api/v1/recipes/{$recipe->id}/images");

        $response->assertOk();

        $response->assertJsonCount(count($images), 'data');
    }

    public function testStoreCreatesANewImageForARecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/images", [
            'image' => UploadedFile::fake()->image('image.jpg'),
        ]);

        $response->assertCreated();

        $image = RecipeImage::find($response->json('data.id'));

        $this->storageFake->assertExists($image->image_path);
    }

    public function testStoreFailsWhenUserCantUpdateRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['is_public' => true]);

        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/images", [
            'image' => UploadedFile::fake()->image('image.jpg'),
        ]);

        $response->assertNotFound();
    }

    public function testStoreSucceedsWhenUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create(['is_public' => false]);

        $response = $this->postJson("/api/v1/recipes/{$recipe->id}/images", [
            'image' => UploadedFile::fake()->image('image.jpg'),
        ]);

        $response->assertCreated();
    }

    public function testDestroyDeletesAnImageForARecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create([
            'user_id' => $user->id,
            'is_public' => false,
        ]);

        $image = $this->createImagesForRecipe($recipe, 1)->first();

        $response = $this->deleteJson("/api/v1/recipe-images/{$image->id}");

        $response->assertNoContent();

        $this->storageFake->assertMissing($image->image_path);
    }

    public function testDestroyFailsWhenUserCantUpdateRecipe() {
        $user = $this->createAndLoginUser(isAdmin: false);

        $recipe = Recipe::factory()->create(['is_public' => true]);

        $image = $this->createImagesForRecipe($recipe, 1)->first();

        $response = $this->deleteJson("/api/v1/recipe-images/{$image->id}");

        $response->assertNotFound();
    }

    public function testDestroySucceedsWhenUserIsAdmin() {
        $user = $this->createAndLoginUser(isAdmin: true);

        $recipe = Recipe::factory()->create(['is_public' => false]);

        $image = $this->createImagesForRecipe($recipe, 1)->first();

        $response = $this->deleteJson("/api/v1/recipe-images/{$image->id}");

        $response->assertNoContent();
    }
}
