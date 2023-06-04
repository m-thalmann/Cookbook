<?php

namespace Database\Seeders;

use App\Models\Recipe;
use App\Models\Cookbook;
use App\Models\Ingredient;
use App\Models\RecipeImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Exception;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder {
    const USER_AMOUNT = 3;
    const RECIPE_AMOUNT = 10;
    const COOKBOOK_AMOUNT = 3;

    public function run() {
        if (!app()->environment('demo')) {
            throw new Exception('Demo mode is not enabled.');
        }

        $demoUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'demo@example.com',
            'password' => Hash::make('demo'),
        ]);

        $users = [
            $demoUser,
            ...User::factory()
                ->count(self::USER_AMOUNT)
                ->create(),
        ];
        $cookbooks = Cookbook::factory()
            ->count(self::COOKBOOK_AMOUNT)
            ->create();

        $recipes = collect([]);

        foreach ($cookbooks as $cookbook) {
            $cookbook->users()->attach($demoUser, ['is_admin' => true]);
        }

        foreach ($users as $user) {
            for ($i = 0; $i < self::RECIPE_AMOUNT; $i++) {
                $recipes->add(
                    Recipe::factory()
                        ->for($user)
                        ->make([
                            'cookbook_id' => fake()->randomElement($cookbooks)
                                ->id,
                        ])
                );
            }

            $recipes = $recipes->merge(
                Recipe::factory()
                    ->count(self::RECIPE_AMOUNT)
                    ->for($user)
                    ->make()
            );
        }

        $this->command->withProgressBar($recipes->shuffle(), function (
            Recipe $recipe
        ) {
            $recipe->save();

            Ingredient::factory()
                ->count(rand(1, 10))
                ->for($recipe)
                ->sequence(fn($sequence) => ['order_index' => $sequence->index])
                ->create();

            try {
                RecipeImage::factory()
                    ->count(rand(0, 3))
                    ->for($recipe)
                    ->create();
            } catch (\Exception $e) {
                echo 'Error fetching random image' . PHP_EOL;
            }
        });
    }
}
