<?php

namespace App\OpenApi\Responses\Users;

use App\OpenApi\Schemas\DetailedUserSchema;
use App\OpenApi\Schemas\PaginationSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class UserIndexResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    PaginationSchema::withDataSchema(DetailedUserSchema::ref())
                )
            );
    }
}
