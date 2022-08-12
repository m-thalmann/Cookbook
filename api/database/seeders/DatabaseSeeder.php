<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeCollection;
use App\Models\RecipeImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder {
    const TABLES_TO_TRUNCATE = [
        'users',
        'password_resets',
        'failed_jobs',
        'recipes',
        'ingredients',
        'recipe_images',
    ];

    const RECIPE_AMOUNT = 10;
    const RECIPE_COLLECTION_USERS_AMOUNT = 5;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        $this->truncateTables();
        $this->clearImages();

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $recipes = Recipe::factory(self::RECIPE_AMOUNT)->create([
            'user_id' => $user->id,
        ]);

        $collection = RecipeCollection::factory()->create();
        $collection->users()->attach($user->id, ['is_admin' => true]);

        foreach ($recipes as $recipe) {
            Ingredient::factory(rand(1, 10))->create([
                'recipe_id' => $recipe,
            ]);

            RecipeImage::factory(rand(0, 3))->create([
                'recipe_id' => $recipe,
            ]);

            if (Arr::random([true, false])) {
                $recipe->update(['recipe_collection_id' => $collection->id]);
            }
        }

        for ($i = 0; $i < self::RECIPE_COLLECTION_USERS_AMOUNT; $i++) {
            $collection->users()->attach(User::factory()->create()->id, [
                'is_admin' => Arr::random([true, false]),
            ]);
        }
    }

    private function truncateTables() {
        Model::unguard();

        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES_TO_TRUNCATE as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();

        Model::reguard();
    }

    private function clearImages() {
        RecipeImage::deleteAllImages();
    }
}
