<?php

namespace App\OpenApi\Responses\Auth\Tokens;

use App\OpenApi\Schemas\AuthTokenSchema;
use App\OpenApi\Schemas\PaginationSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class TokenIndexResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    PaginationSchema::withDataSchema(AuthTokenSchema::ref())
                )
            );
    }
}
