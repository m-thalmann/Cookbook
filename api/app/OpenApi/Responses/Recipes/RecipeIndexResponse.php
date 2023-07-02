<?php

namespace App\OpenApi\Responses\Recipes;

use App\OpenApi\Schemas\ListRecipeSchema;
use App\OpenApi\Schemas\PaginationSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class RecipeIndexResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    PaginationSchema::withDataSchema(ListRecipeSchema::ref())
                )
            );
    }
}
