<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    const RECIPE_AMOUNT = 10;

    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run() {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $recipes = Recipe::factory(self::RECIPE_AMOUNT)->create([
            'user_id' => $user->id,
        ]);

        foreach ($recipes as $recipe) {
            Ingredient::factory(rand(1, 10))->create([
                'recipe_id' => $recipe,
            ]);
        }
    }
}
