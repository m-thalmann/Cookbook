<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() {
        return [
            'user_id' => User::factory(),
            'is_public' => false,
            'language_code' => 'en',
            'name' => $this->faker->name(),
            'category' => $this->faker->name(),
            'portions' => rand(1, 8),
            'difficulty' => rand(1, 5),
            'preparation' => '<ol><li>Get ingredients</li></ol>',
            'preparation_time_minutes' => rand(10, 60),
            'resting_time_minutes' => rand(10, 120),
            'cooking_time_minutes' => rand(10, 120),
        ];
    }
}

