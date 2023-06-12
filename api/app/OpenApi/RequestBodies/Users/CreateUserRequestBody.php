<?php

namespace App\OpenApi\RequestBodies\Users;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class CreateUserRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::string('name')
                            ->maxLength(255)
                            ->description('The user\'s last name')
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
                            'Whether the created user is an admin-user'
                        ),
                        Schema::boolean('is_verified')->description(
                            'Whether the user\'s email should be marked as verified (default false)'
                        ),
                        Schema::boolean('send_verification_email')->description(
                            'Whether to send a verification email to the user, if the email is not verified (default true)'
                        )
                    )
                    ->required('name', 'email', 'password')
            )
        );
    }
}
