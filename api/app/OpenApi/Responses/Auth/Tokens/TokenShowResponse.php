<?php

namespace App\OpenApi\Responses\Auth\Tokens;

use App\OpenApi\Schemas\AuthTokenSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class TokenShowResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(AuthTokenSchema::ref('data'))
                        ->required('data')
                )
            );
    }
}
