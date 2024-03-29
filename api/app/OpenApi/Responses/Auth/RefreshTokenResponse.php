<?php

namespace App\OpenApi\Responses\Auth;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class RefreshTokenResponse extends ResponseFactory {
    public function build(): Response {
        return Response::created()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::object('data')
                                ->properties(
                                    Schema::string('access_token')
                                        ->description('The access token')
                                        ->example(
                                            'OqcQ5yVwyXxUmvynnKLcN5vQV73tTvp02HHu9IcJGE8mDIVDRhdOli18DZs7YMYq'
                                        ),
                                    Schema::string('refresh_token')
                                        ->description('The refresh token')
                                        ->example(
                                            'uOky50sbsedxDSBVJxEE9v8M1MiGOU27JcS9yhn2JZXIq9TTyrYHPyq3jUk6xPzp'
                                        )
                                )
                                ->required('access_token', 'refresh_token')
                        )
                        ->required('data')
                )
            );
    }
}
