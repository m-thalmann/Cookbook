<?php

namespace App\Console\Commands;

use App\Models\RecipeImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PruneRecipeImages extends Command {
    protected $signature = 'recipeImages:prune';

    protected $description = 'Deletes all recipe-images from storage that do not have a corresponding entry in the database';

    public function handle() {
        if (!Schema::hasTable(app(RecipeImage::class)->getTable())) {
            $this->warn(
                'No images have been deleted since the recipe-image table does not exist.'
            );

            return 0;
        }

        $amountDeleted = RecipeImage::pruneImages();

        $this->info("$amountDeleted images have been deleted.");

        return 0;
    }
}
