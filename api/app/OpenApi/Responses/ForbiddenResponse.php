<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class ForbiddenResponse extends ResponseFactory {
    public function build(): Response {
        return Response::forbidden()
            ->description(
                'The user is not authorized to perform this action / access this resource'
            )
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::string('message')->example('Forbidden.')
                        )
                        ->required('message')
                )
            );
    }
}
