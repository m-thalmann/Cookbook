<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class CookbookSchema extends SchemaFactory implements Reusable {
    public function build(): SchemaContract {
        return Schema::object('Cookbook')
            ->properties(
                Schema::integer('id')
                    ->description('The id of the cookbook')
                    ->example(1),
                Schema::string('name')
                    ->description('The name of the cookbook')
                    ->example('John\'s Cookbook'),
                Schema::integer('created_at')
                    ->description(
                        'The unix-timestamp when the cookbook was created'
                    )
                    ->example(1660997908),
                Schema::integer('updated_at')
                    ->description(
                        'The unix-timestamp when the cookbook was last updated'
                    )
                    ->example(1660997908)
            )
            ->required('id', 'name', 'created_at', 'updated_at');
    }
}

