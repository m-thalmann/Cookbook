<?php

namespace App\OpenApi\RequestBodies\Recipes;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class UpdateRecipeRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::integer('user_id')
                            ->description('The id of the new user')
                            ->example(2),
                        Schema::string('name')
                            ->maxLength(255)
                            ->description('The name of the recipe')
                            ->example('Chocolate Cake'),
                        Schema::boolean('is_public')->description(
                            'Whether the recipe is publicly visible (for all users)'
                        ),
                        Schema::boolean('is_shared')->description(
                            'Whether the recipe should have a share-uuid to be shared. If changed to true will create a new share-uuid'
                        ),
                        Schema::string('language_code')
                            ->minLength(2)
                            ->maxLength(2)
                            ->description(
                                'The recipes\'s language as a two-character code'
                            )
                            ->example('en'),
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
                            ->example(2)
                    )
                    ->required()
            )
        );
    }
}

