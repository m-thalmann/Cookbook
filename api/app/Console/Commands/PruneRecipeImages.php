<?php

namespace App\Console\Commands;

use App\Models\RecipeImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PruneRecipeImages extends Command {
    protected $signature = 'recipeImages:prune';

    protected $description = 'Deletes all recipe-images from storage that do not have a corresponding entry in the database';

    public function handle() {
        if (!Schema::hasTable(app(RecipeImage::class)->getTable())) {
            $this->warn(
                'All images have been deleted since the recipe-image table does not exist.'
            );

            return 0;
        }

        $databaseImages = RecipeImage::query()
            ->get('image_path')
            ->pluck('image_path')
            ->toArray();

        $storageImages = Storage::disk('public')->allFiles(
            RecipeImage::IMAGE_DIRECTORY
        );

        $pathToDelete = [];

        foreach ($storageImages as $image) {
            if (!in_array($image, $databaseImages)) {
                $pathToDelete[] = $image;
            }
        }

        $amountToDelete = count($pathToDelete);

        if ($amountToDelete > 0) {
            Storage::disk('public')->delete($pathToDelete);
        }

        $this->info("$amountToDelete images have been deleted.");

        return 0;
    }
}

