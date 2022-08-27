<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class ValidationErrorResponse extends ResponseFactory {
    public function build(): Response {
        return Response::unprocessableEntity()
            ->description('There have been validation errors')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::string('message')
                                ->description(
                                    'The validation message. Might not be translated; use the individual error fields instead'
                                )
                                ->deprecated()
                                ->example('The email field is required.'),
                            Schema::object('errors')
                                ->additionalProperties(
                                    Schema::array()
                                        ->items(
                                            Schema::string()
                                                ->description(
                                                    'The error message'
                                                )
                                                ->example(
                                                    'The email field is required.'
                                                )
                                        )
                                        ->minItems(1)
                                )
                                ->description(
                                    'All found errors with the key being the field of the error and the value the list of errors encountered'
                                )
                        )
                        ->required('message', 'errors')
                )
            );
    }
}
