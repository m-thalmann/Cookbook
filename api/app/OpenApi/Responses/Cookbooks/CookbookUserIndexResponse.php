<?php

namespace App\OpenApi\Responses\Cookbooks;

use App\OpenApi\Schemas\CookbookUserSchema;
use App\OpenApi\Schemas\PaginationSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class CookbookUserIndexResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    PaginationSchema::withDataSchema(CookbookUserSchema::ref())
                )
            );
    }
}

