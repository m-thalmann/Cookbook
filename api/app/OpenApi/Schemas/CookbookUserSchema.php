<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class CookbookUserSchema extends UserSchema {
    public function getName(): string {
        return 'CookbookUser';
    }

    public function getProperties(): array {
        return [
            ...parent::getProperties(),

            Schema::object('meta')
                ->description('Meta information about the relation')
                ->properties(
                    Schema::boolean('is_admin')->description(
                        'Whether the user is an admin of the cookbook'
                    ),
                    Schema::integer('created_at')
                        ->description(
                            'The unix-timestamp when the user was added to the cookbook'
                        )
                        ->example(1660997908),
                    Schema::integer('updated_at')
                        ->description(
                            'The unix-timestamp when the cookbook-user was last updated'
                        )
                        ->example(1660997908)
                )
                ->required('meta', 'created_at', 'updated_at'),
        ];
    }

    public function getRequired(): array {
        return [...parent::getRequired(), 'meta'];
    }
}
