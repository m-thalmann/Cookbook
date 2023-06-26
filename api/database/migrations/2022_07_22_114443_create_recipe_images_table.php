<?php

use App\Models\RecipeImage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('recipe_images', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('recipe_id')
                ->constrained('recipes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('image_path');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        app(RecipeImage::class)->deleteAllImages();

        Schema::dropIfExists('recipe_images');
    }
};
