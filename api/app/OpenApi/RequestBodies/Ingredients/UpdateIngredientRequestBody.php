<?php

namespace App\OpenApi\RequestBodies\Ingredients;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class UpdateIngredientRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        $createIngredientSchema = new CreateIngredientRequestBody();

        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(...$createIngredientSchema->getProperties())
                    ->required()
            )
        );
    }
}

