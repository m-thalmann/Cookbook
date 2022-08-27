<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class ConflictResponse extends ResponseFactory {
    public function build(): Response {
        return Response::create()
            ->statusCode(409)
            ->description('Conflict')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::string('message')->example(
                                'This user can\'t be deleted.'
                            )
                        )
                        ->required('message')
                )
            );
    }
}

