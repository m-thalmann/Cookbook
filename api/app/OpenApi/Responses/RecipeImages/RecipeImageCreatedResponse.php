<?php

namespace App\OpenApi\Responses\RecipeImages;

use App\OpenApi\Schemas\RecipeImageSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class RecipeImageCreatedResponse extends ResponseFactory {
    public function build(): Response {
        return Response::created()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(RecipeImageSchema::ref('data'))
                        ->required('data')
                )
            );
    }
}

