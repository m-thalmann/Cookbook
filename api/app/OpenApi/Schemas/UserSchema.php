<?php

namespace App\OpenApi\Schemas;

use App\OpenApi\ExtendableSchemaFactory;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class UserSchema extends ExtendableSchemaFactory {
    public function getName(): string {
        return 'User';
    }

    public function getProperties(): array {
        return [
            Schema::integer('id')
                ->description('The id of the user')
                ->example(1),
            Schema::string('name')
                ->description('The user\'s full name')
                ->example('John Doe'),
            Schema::string('email')
                ->description('The user\'s email')
                ->example('john.doe@example.com'),
            Schema::string('language_code')
                ->description('The user\'s language as a two-character code')
                ->nullable()
                ->example('en'),
        ];
    }

    public function getRequired(): array {
        return ['id', 'name', 'email', 'language_code'];
    }
}
