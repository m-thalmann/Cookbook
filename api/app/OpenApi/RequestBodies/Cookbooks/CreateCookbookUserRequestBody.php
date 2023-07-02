<?php

namespace App\OpenApi\RequestBodies\Cookbooks;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class CreateCookbookUserRequestBody extends RequestBodyFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(
                        Schema::integer('user_id')
                            ->description('The id of the user to add')
                            ->example(2),
                        Schema::boolean('is_admin')->description(
                            'Whether the user should be an admin of the cookbook'
                        )
                    )
                    ->required('user_id', 'is_admin')
            )
        );
    }
}
