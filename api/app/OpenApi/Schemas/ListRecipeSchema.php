<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\OneOf;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class ListRecipeSchema extends RecipeSchema {
    public function getName(): string {
        return 'ListRecipe';
    }

    public function getProperties(): array {
        return [
            ...parent::getProperties(),

            OneOf::create('thumbnail')->schemas(
                RecipeImageSchema::ref('thumbnail')->description(
                    'The image to use as thumbnail for the recipe'
                ),
                Schema::create()->type('null')
            ),

            CookbookSchema::ref('cookbook')->description(
                'The cookbook this recipe belongs to. Only present if the user is part of it'
            ),
        ];
    }

    public function getRequired(): array {
        return [...parent::getRequired(), 'thumbnail'];
    }
}
