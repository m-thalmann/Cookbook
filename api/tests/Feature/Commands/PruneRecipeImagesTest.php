<?php

namespace Tests\Feature\Commands;

use App\Models\Recipe;
use App\Models\RecipeImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class PruneRecipeImagesTest extends TestCase {
    use MockeryPHPUnitIntegration;

    public function testItDeletesUnusedImages() {
        /**
         * @var FilesystemAdapter
         */
        $storageFake = Storage::fake('public');

        $unusedPath = RecipeImage::IMAGE_DIRECTORY . '/1.jpg';
        $usedPath = RecipeImage::IMAGE_DIRECTORY . '/2.jpg';

        $recipe = Recipe::factory()->create();
        $image = $recipe->images()->make();
        $image->image_path = $usedPath;
        $image->save();

        $storageFake->put($unusedPath, 'test');
        $storageFake->put($usedPath, 'test');

        $statusCode = $this->artisan('recipeImages:prune')->run();
        $this->assertEquals(0, $statusCode);

        $storageFake->assertMissing($unusedPath);
        $storageFake->assertExists($usedPath);
    }

    public function testItDoesNothingIfTheImagesTableDoesNotExist() {
        /**
         * @var RecipeImage|MockInterface
         */
        $recipeImageMock = Mockery::mock(RecipeImage::class)->makePartial();
        app()->instance(RecipeImage::class, $recipeImageMock);

        $recipeImageMock
            ->shouldReceive('getTable')
            ->andReturn('non-existing-table');

        $recipeImageMock->shouldNotReceive('pruneImages');

        $statusCode = $this->artisan('recipeImages:prune')->run();
        $this->assertEquals(0, $statusCode);
    }
}
