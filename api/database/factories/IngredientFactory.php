<?php

namespace Database\Factories;

use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ingredient>
 */
class IngredientFactory extends Factory {
    const NAMES = [
        'Flour',
        'Sugar',
        'Salt',
        'Eggs',
        'Milk',
        'Butter',
        'Baking Powder',
        'Olive Oil',
        'Yeast',
        'Garlic',
        'Onion',
        'Tomato',
        'Potato',
        'Carrot',
        'Lemon',
        'Rice',
        'Pasta',
        'Bread',
        'Water',
    ];

    const UNITS = ['g', 'kg', 'ml', 'l'];

    public function definition() {
        return [
            'recipe_id' => Recipe::factory(),
            'name' => $this->faker->randomElement(self::NAMES),
            'amount' => rand(4, 1000) * 0.25,
            'unit' => $this->faker->randomElement(self::UNITS),
            'group' => null,
        ];
    }
}
