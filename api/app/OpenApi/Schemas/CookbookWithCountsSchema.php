<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class CookbookWithCountsSchema extends CookbookSchema {
    public function getName(): string {
        return 'CookbookWithCounts';
    }

    public function getProperties(): array {
        return [
            ...parent::getProperties(),

            Schema::integer('recipes_count')
                ->description('The amount of recipes in this cookbook')
                ->example(12),

            Schema::integer('users_count')
                ->description('The amount of users in this cookbook')
                ->example(4),
        ];
    }

    public function getRequired(): array {
        return [...parent::getRequired(), 'recipes_count', 'users_count'];
    }
}
