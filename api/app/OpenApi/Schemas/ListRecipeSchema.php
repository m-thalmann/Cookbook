<?php

namespace App\OpenApi\Schemas;

class ListRecipeSchema extends RecipeSchema {
    public function getName(): string {
        return 'ListRecipe';
    }

    public function getProperties(): array {
        return [
            ...parent::getProperties(),

            RecipeImageSchema::ref('thumbnail')
                ->description('The image to use as thumbnail for the recipe')
                ->nullable(),

            CookbookSchema::ref('cookbook')->description(
                'The cookbook this recipe belongs to. Only present if the user is part of it'
            ),
        ];
    }

    public function getRequired(): array {
        return [...parent::getRequired(), 'thumbnail'];
    }
}

