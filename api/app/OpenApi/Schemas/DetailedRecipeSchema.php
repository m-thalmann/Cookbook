<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class DetailedRecipeSchema extends RecipeSchema {
    public function getName(): string {
        return 'DetailedRecipe';
    }

    public function getProperties(): array {
        return [
            ...parent::getProperties(),

            Schema::string('share_uuid')
                ->description(
                    'The token to use for sharing this recipe. `NULL` if disabled. Is only included if the user is allowed to edit it'
                )
                ->nullable()
                ->example('8a221319-e879-421f-88e3-549b1609d916'),

            Schema::array('ingredients')
                ->items(
                    Schema::object()
                        ->properties(
                            Schema::string('group')
                                ->nullable()
                                ->description(
                                    'The group-name of the ingredient-group'
                                )
                                ->example('Topping'),
                            Schema::array('items')->items(
                                IngredientSchema::ref()
                            )
                        )
                        ->required('group', 'items')
                )
                ->description(
                    'The ingredients of the recipe grouped by their group'
                ),

            Schema::array('images')
                ->items(RecipeImageSchema::ref())
                ->description('The images for the recipe'),

            CookbookSchema::ref('cookbook')->description(
                'The cookbook this recipe belongs to. Only present if the user is part of it'
            ),
        ];
    }

    public function getRequired(): array {
        return [...parent::getRequired(), 'ingredients', 'images'];
    }
}

