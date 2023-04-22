<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecipeImage>
 */
class RecipeImageFactory extends Factory {
    const RANDOM_IMAGE_URLS = [
        'https://loremflickr.com/640/480/drinks',
        'https://loremflickr.com/640/480/fastfood',
        'https://loremflickr.com/640/480/food',
        'https://loremflickr.com/640/480/dessert',
        'https://loremflickr.com/640/480/cake',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition() {
        $contents = file_get_contents(
            $this->faker->randomElement(self::RANDOM_IMAGE_URLS)
        );

        $path = RecipeImage::IMAGE_DIRECTORY . '/' . Str::random(32) . '.jpg';

        Storage::disk('public')->put($path, $contents);

        return [
            'recipe_id' => Recipe::factory(),
            'image_path' => $path,
        ];
    }
}
