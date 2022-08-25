<?php

namespace App\OpenApi\RequestBodies\Auth;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class RegisterRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()
            ->description('Register request')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::string('first_name')
                                ->maxLength(255)
                                ->description('The user\'s first name')
                                ->example('John'),
                            Schema::string('last_name')
                                ->maxLength(255)
                                ->description('The user\'s last name')
                                ->example('Doe'),
                            Schema::string('email')
                                ->maxLength(255)
                                ->description('The user\'s email')
                                ->example('john.doe@example.com'),
                            Schema::string('password')
                                ->description('The user\'s password')
                                ->example('password'),
                            Schema::string('password_confirmation')
                                ->description('The user\'s password (repeated)')
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
                                ->example(
                                    '10000000-aaaa-bbbb-cccc-000000000001'
                                )
                        )
                        ->required(
                            'first_name',
                            'last_name',
                            'email',
                            'password',
                            'password_confirmation'
                        )
                )
            );
    }
}

