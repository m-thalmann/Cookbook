<?php

namespace App\OpenApi\RequestBodies\Recipes;

use App\OpenApi\RequestBodies\Ingredients\CreateIngredientRequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class CreateRecipeRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        $createIngredientSchema = new CreateIngredientRequestBody();

        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::string('name')
                            ->maxLength(255)
                            ->description('The name of the recipe')
                            ->example('Chocolate Cake'),
                        Schema::boolean('is_public')->description(
                            'Whether the recipe is publicly visible (for all users)'
                        ),
                        Schema::string('description')
                            ->description('The recipe\'s description')
                            ->nullable(),
                        Schema::string('category')
                            ->description('The recipe\'s category')
                            ->nullable()
                            ->example('Cakes'),
                        Schema::integer('portions')
                            ->description(
                                'The amount of portions this recipe will yield'
                            )
                            ->nullable()
                            ->example(10),
                        Schema::integer('difficulty')
                            ->description(
                                'The difficulty to prepare the recipe on a scale from 1-5'
                            )
                            ->nullable()
                            ->example(3),
                        Schema::string('preparation')
                            ->maxLength(2000)
                            ->description(
                                'The preparation instructions as (sanitized) HTML'
                            )
                            ->nullable(),
                        Schema::integer('preparation_time_minutes')
                            ->description(
                                'The amount of minutes needed to prepare the recipe'
                            )
                            ->nullable(),
                        Schema::integer('resting_time_minutes')
                            ->description(
                                'The amount of minutes the prepared recipe has to rest'
                            )
                            ->nullable(),
                        Schema::integer('cooking_time_minutes')
                            ->description(
                                'The amount of minutes the prepared recipe has to cook / bake'
                            )
                            ->nullable(),
                        Schema::integer('cookbook_id')
                            ->description(
                                'The id of the cookbook this recipe belongs to'
                            )
                            ->nullable()
                            ->example(2),
                        Schema::array('ingredients')
                            ->items(
                                Schema::object()
                                    ->properties(
                                        ...$createIngredientSchema->getProperties()
                                    )
                                    ->required(
                                        ...$createIngredientSchema->getRequired()
                                    )
                            )
                            ->description(
                                'The ingredients to add to the recipe'
                            )
                    )
                    ->required('name')
            )
        );
    }
}
