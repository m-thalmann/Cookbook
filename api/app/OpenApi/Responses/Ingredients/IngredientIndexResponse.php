<?php

namespace App\OpenApi\Responses\Ingredients;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class IngredientIndexResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::array('data')->items(
                                Schema::object()
                                    ->properties(
                                        Schema::string('name')
                                            ->maxLength(40)
                                            ->description(
                                                'The name of the ingredient'
                                            )
                                            ->example('Sugar'),
                                        Schema::string('unit')
                                            ->maxLength(20)
                                            ->description(
                                                'The unit associated with the amount for this ingredient'
                                            )
                                            ->example('g')
                                    )
                                    ->required('name', 'unit')
                            )
                        )
                        ->required('data')
                )
            );
    }
}

