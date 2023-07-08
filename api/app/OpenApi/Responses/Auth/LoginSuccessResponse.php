<?php

namespace App\OpenApi\Responses\Auth;

use App\Http\Controllers\Auth\AuthenticationController;
use App\OpenApi\Schemas\DetailedUserSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Header;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class LoginSuccessResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::object('data')
                                ->properties(
                                    DetailedUserSchema::ref('user'),
                                    Schema::string('access_token')
                                        ->description('The access token')
                                        ->example(
                                            'OqcQ5yVwyXxUmvynnKLcN5vQV73tTvp02HHu9IcJGE8mDIVDRhdOli18DZs7YMYq'
                                        ),
                                    Schema::string('refresh_token')
                                        ->description('The refresh token')
                                        ->nullable()
                                        ->example(
                                            'uOky50sbsedxDSBVJxEE9v8M1MiGOU27JcS9yhn2JZXIq9TTyrYHPyq3jUk6xPzp'
                                        )
                                )
                                ->required(
                                    'user',
                                    'access_token',
                                    'refresh_token'
                                )
                        )
                        ->required('data')
                )
            )
            ->headers(
                Header::create(
                    AuthenticationController::EMAIL_UNVERIFIED_HEADER
                )
                    ->required(false)
                    ->description(
                        'If the user is unverified, this header will be set to true'
                    )
            );
    }
}
