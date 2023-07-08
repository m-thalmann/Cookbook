<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class RecipeImageSchema extends SchemaFactory implements Reusable {
    public function build(): SchemaContract {
        return Schema::object('RecipeImage')
            ->properties(
                Schema::integer('id')
                    ->description('The id of the image')
                    ->example(1),
                Schema::integer('recipe_id')
                    ->description('The id of the recipe this image belongs to')
                    ->example(2),
                Schema::integer('created_at')
                    ->description(
                        'The unix-timestamp when the image was created'
                    )
                    ->example(1660997908),
                Schema::integer('updated_at')
                    ->description(
                        'The unix-timestamp when the image was last updated'
                    )
                    ->example(1660997908),
                Schema::string('url')
                    ->description('The url from where to fetch the image')
                    ->example(
                        'https://example.com/api/storage/images/recipes/mgCpsmOBartSGGr1s6EYUc28Jza5xv9G.jpg'
                    )
            )
            ->required('id', 'recipe_id', 'created_at', 'updated_at', 'url');
    }
}
