<?php

namespace App\OpenApi\Schemas;

use App\OpenApi\ExtendableSchemaFactory;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class RecipeSchema extends ExtendableSchemaFactory {
    public function getName(): string {
        return 'Recipe';
    }

    public function getProperties(): array {
        return [
            Schema::integer('id')
                ->description('The id of the recipe')
                ->example(1),
            Schema::integer('user_id')
                ->description('The id of the user that owns this recipe')
                ->example(1),
            Schema::integer('cookbook_id')
                ->description('The id of the cookbook this recipe belongs to')
                ->nullable()
                ->example(2),
            Schema::boolean('is_public')->description(
                'Whether the recipe is publicly visible (for all users)'
            ),
            Schema::string('language_code')
                ->minLength(2)
                ->maxLength(2)
                ->description('The recipe\'s language as a two-character code')
                ->example('en'),
            Schema::string('name')
                ->description('The name of the recipe')
                ->example('Chocolate Cake'),
            Schema::string('description')
                ->description('The recipe\'s description')
                ->nullable(),
            Schema::string('category')
                ->description('The recipe\'s category')
                ->nullable()
                ->example('Cakes'),
            Schema::integer('portions')
                ->description('The amount of portions this recipe will yield')
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
            Schema::string('deleted_at')
                ->description('The unix-timestamp when the recipe was deleted')
                ->nullable()
                ->example(null),
            Schema::integer('created_at')
                ->description('The unix-timestamp when the recipe was created')
                ->example(1660997908),
            Schema::integer('updated_at')
                ->description(
                    'The unix-timestamp when the recipe was last updated'
                )
                ->example(1660997908),
            UserSchema::ref('user')->description(
                'The user that owns this recipe'
            ),
        ];
    }

    public function getRequired(): array {
        return [
            'id',
            'user_id',
            'cookbook_id',
            'is_public',
            'language_code',
            'name',
            'description',
            'category',
            'portions',
            'difficulty',
            'preparation',
            'preparation_time_minutes',
            'resting_time_minutes',
            'cooking_time_minutes',
            'deleted_at',
            'created_at',
            'updated_at',
            'user',
        ];
    }
}

