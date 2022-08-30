<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Cookbook;
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
    const COOKBOOK_USERS_AMOUNT = 5;

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
            'email' => 'john.doe@example.com',
        ]);

        $recipes = Recipe::factory(self::RECIPE_AMOUNT)->create([
            'user_id' => $user->id,
        ]);

        $cookbook = Cookbook::factory()->create();
        $cookbook->users()->attach($user->id, ['is_admin' => true]);

        foreach ($recipes as $recipe) {
            Ingredient::factory(rand(1, 10))->create([
                'recipe_id' => $recipe,
            ]);

            RecipeImage::factory(rand(0, 3))->create([
                'recipe_id' => $recipe,
            ]);

            if (Arr::random([true, false])) {
                $recipe->update(['cookbook_id' => $cookbook->id]);
            }
        }

        for ($i = 0; $i < self::COOKBOOK_USERS_AMOUNT; $i++) {
            $cookbook->users()->attach(User::factory()->create()->id, [
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
