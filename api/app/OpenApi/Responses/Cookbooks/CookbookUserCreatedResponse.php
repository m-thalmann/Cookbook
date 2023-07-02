<?php

namespace App\OpenApi\Responses\Cookbooks;

use App\OpenApi\Schemas\CookbookUserSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class CookbookUserCreatedResponse extends ResponseFactory {
    public function build(): Response {
        return Response::created()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(CookbookUserSchema::ref('data'))
                        ->required('data')
                )
            );
    }
}
