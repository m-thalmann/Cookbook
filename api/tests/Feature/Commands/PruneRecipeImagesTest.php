<?php

namespace Tests\Feature\Commands;

use App\Models\Recipe;
use App\Models\RecipeImage;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class PruneRecipeImagesTest extends TestCase {
    use MockeryPHPUnitIntegration;

    public function testItDeletesUnusedImages() {
        /**
         * @var RecipeImage|MockInterface
         */
        $recipeImageMock = Mockery::mock(RecipeImage::class)->makePartial();
        app()->instance(RecipeImage::class, $recipeImageMock);

        $recipeImageMock->shouldReceive('getTable')->andReturn('recipe_images');

        $amountDeleted = 2;

        $recipeImageMock
            ->shouldReceive('pruneImages')
            ->once()
            ->andReturn($amountDeleted);

        $statusCode = $this->artisan('recipeImages:prune')->run();
        $this->assertEquals(0, $statusCode);
    }
}
