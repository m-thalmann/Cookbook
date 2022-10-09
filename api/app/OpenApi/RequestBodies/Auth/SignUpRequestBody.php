<?php

namespace App\OpenApi\RequestBodies\Auth;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class SignUpRequestBody extends RequestBodyFactory {
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
                        Schema::string('hcaptcha_token')
                            ->description(
                                'The hCaptcha token for the request to verify. Required if hCaptcha is enabled'
                            )
                            ->example('10000000-aaaa-bbbb-cccc-000000000001')
                    )
                    ->required('name', 'email', 'password')
            )
        );
    }
}

