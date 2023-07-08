<?php

namespace App\OpenApi\Responses\Auth;

use App\Http\Controllers\Auth\AuthenticationController;
use App\OpenApi\Schemas\DetailedUserSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Header;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class UserResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(DetailedUserSchema::ref('data'))
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
