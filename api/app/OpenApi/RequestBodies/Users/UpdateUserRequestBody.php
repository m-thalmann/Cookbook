<?php

namespace App\OpenApi\RequestBodies\Users;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class UpdateUserRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::string('name')
                            ->maxLength(255)
                            ->description('The user\'s full name')
                            ->example('John Doe'),
                        Schema::string('email')
                            ->maxLength(255)
                            ->description('The user\'s email')
                            ->example('john.doe@example.com'),
                        Schema::string('password')
                            ->description('The user\'s password')
                            ->example('password'),
                        Schema::string('language_code')
                            ->minLength(2)
                            ->maxLength(2)
                            ->nullable()
                            ->description(
                                'The user\'s language as a two-character code'
                            )
                            ->example('en'),
                        Schema::boolean('is_admin')->description(
                            'Whether the user is an admin-user'
                        ),
                        Schema::boolean('is_verified')->description(
                            'Whether the user\'s email should be marked as verified'
                        ),
                        Schema::boolean('do_logout')->description(
                            'Whether the user should be logged out from all sessions after updating'
                        ),
                        Schema::string('current_password')
                            ->description(
                                'The user\'s current password (required if email or password are changed)'
                            )
                            ->example('password')
                    )
                    ->required()
            )
        );
    }
}

