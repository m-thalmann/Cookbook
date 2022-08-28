<?php

namespace App\OpenApi\RequestBodies\Auth;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class ResetPasswordRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::string('token')->description(
                            'The password-reset-token'
                        ),
                        Schema::string('email')
                            ->description('The user\'s email')
                            ->example('john.doe@example.com'),
                        Schema::string('password')
                            ->description('The new password for the user')
                            ->example('password'),
                        Schema::string('password_confirmation')
                            ->description(
                                'The new password for the user (repeated)'
                            )
                            ->example('password')
                    )
                    ->required(
                        'token',
                        'email',
                        'password',
                        'password_confirmation'
                    )
            )
        );
    }
}

