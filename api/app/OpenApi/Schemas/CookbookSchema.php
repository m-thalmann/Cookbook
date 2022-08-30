<?php

namespace App\OpenApi\Schemas;

use App\OpenApi\ExtendableSchemaFactory;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class CookbookSchema extends ExtendableSchemaFactory {
    public function getName(): string {
        return 'Cookbook';
    }

    public function getProperties(): array {
        return [
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
                ->example(1660997908),
        ];
    }

    public function getRequired(): array {
        return ['id', 'name', 'created_at', 'updated_at'];
    }
}

