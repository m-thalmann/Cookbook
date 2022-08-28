<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class IngredientSchema extends SchemaFactory implements Reusable {
    public function build(): SchemaContract {
        return Schema::object('Ingredient')
            ->properties(
                Schema::integer('id')
                    ->description('The id of the ingredient')
                    ->example(1),
                Schema::integer('recipe_id')
                    ->description(
                        'The id of the recipe this ingredient belongs to'
                    )
                    ->example(2),
                Schema::string('name')
                    ->maxLength(40)
                    ->description('The name of the ingredient')
                    ->example('Sugar'),
                Schema::number('amount')
                    ->minimum(0)
                    ->description('The amount of the ingredient to use')
                    ->nullable()
                    ->example(250),
                Schema::string('unit')
                    ->maxLength(20)
                    ->description('The unit associated with the amount')
                    ->nullable()
                    ->example('g'),
                Schema::string('group')
                    ->maxLength(20)
                    ->description(
                        'The ingredient-group this ingredient belongs to'
                    )
                    ->nullable()
                    ->example('Topping'),
                Schema::integer('created_at')
                    ->description(
                        'The unix-timestamp when the ingredient was created'
                    )
                    ->example(1660997908),
                Schema::integer('updated_at')
                    ->description(
                        'The unix-timestamp when the ingredient was last updated'
                    )
                    ->example(1660997908)
            )
            ->required(
                'id',
                'recipe_id',
                'name',
                'amount',
                'unit',
                'group',
                'created_at',
                'updated_at'
            );
    }
}

