<?php

namespace App\OpenApi\Responses\Categories;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class CategoryIndexResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::array('data')->items(
                                Schema::string()
                                    ->description('The name of the category')
                                    ->example('Cakes')
                            )
                        )
                        ->required('data')
                )
            );
    }
}

