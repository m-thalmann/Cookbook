<?php

namespace App\OpenApi\Responses\Recipes;

use App\OpenApi\Schemas\DetailedRecipeSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class RecipeShowResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(DetailedRecipeSchema::ref('data'))
                        ->required('data')
                )
            );
    }
}
