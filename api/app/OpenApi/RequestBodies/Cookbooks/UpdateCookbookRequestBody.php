<?php

namespace App\OpenApi\RequestBodies\Cookbooks;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class UpdateCookbookRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::string('name')
                            ->maxLength(100)
                            ->description('The name of the cookbook')
                            ->example('John\'s Cookbook')
                    )
                    ->required()
            )
        );
    }
}
