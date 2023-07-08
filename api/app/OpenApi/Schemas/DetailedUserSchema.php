<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class DetailedUserSchema extends UserSchema {
    public function getName(): string {
        return 'DetailedUser';
    }

    public function getProperties(): array {
        return [
            ...parent::getProperties(),

            Schema::integer('email_verified_at')
                ->nullable()
                ->description('The unix-timestamp when the email was verified')
                ->example(1660997908),
            Schema::boolean('is_admin')->description(
                'Whether the user is an admin user'
            ),
            Schema::integer('created_at')
                ->description('The unix-timestamp when the user was created')
                ->example(1660997908),
            Schema::integer('updated_at')
                ->description(
                    'The unix-timestamp when the user was last updated'
                )
                ->example(1660997908),
        ];
    }

    public function getRequired(): array {
        return [
            ...parent::getRequired(),

            'email_verified_at',
            'is_admin',
            'created_at',
            'updated_at',
        ];
    }
}
