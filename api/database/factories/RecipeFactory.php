<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory {
    const CATEGORIES = ['Dessert', 'Snack', 'Drink', 'Cakes', 'Meals'];

    public function definition() {
        return [
            'user_id' => User::factory(),
            'cookbook_id' => null,
            'is_public' => $this->faker->boolean(),
            'name' => $this->faker->name(),
            'category' => $this->faker->randomElement(self::CATEGORIES),
            'portions' => rand(1, 8),
            'difficulty' => rand(1, 5),
            'preparation' =>
                '<ol><li>Get ingredients</li><li>Mix ingredients</li><li>Cook ingredients</li><li>Enjoy!</li></ol>',
            'preparation_time_minutes' => rand(10, 60),
            'resting_time_minutes' => rand(10, 120),
            'cooking_time_minutes' => rand(10, 120),
        ];
    }
}
