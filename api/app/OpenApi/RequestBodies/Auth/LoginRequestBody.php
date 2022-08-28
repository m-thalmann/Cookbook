<?php

namespace App\OpenApi\RequestBodies\Auth;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class LoginRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::string('email')
                            ->description('The email of the user')
                            ->example('john.doe@example.com'),
                        Schema::string('password')
                            ->description('The password of the user')
                            ->example('password')
                    )
                    ->required('email', 'password')
            )
        );
    }
}

