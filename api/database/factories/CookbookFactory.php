<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cookbook>
 */
class CookbookFactory extends Factory {
    public function definition() {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
