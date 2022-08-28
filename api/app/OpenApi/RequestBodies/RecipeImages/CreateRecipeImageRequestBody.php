<?php

namespace App\OpenApi\RequestBodies\RecipeImages;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class CreateRecipeImageRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::create()
                ->mediaType('multipart/form-data')
                ->schema(
                    Schema::object()
                        ->properties(
                            Schema::string('image')->format(
                                Schema::FORMAT_BINARY
                            )
                        )
                        ->required('image')
                )
        );
    }
}

