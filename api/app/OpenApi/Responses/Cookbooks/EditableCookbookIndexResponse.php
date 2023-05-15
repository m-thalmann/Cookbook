<?php

namespace App\OpenApi\Responses\Cookbooks;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class EditableCookbookIndexResponse extends ResponseFactory {
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
                                        Schema::string('id')->example('1'),
                                        Schema::string('name')->example(
                                            'My Cookbook'
                                        )
                                    )
                                    ->required('id', 'name')
                            )
                        )
                        ->required('data')
                )
            );
    }
}
