<?php

namespace App\OpenApi\Responses;

use App\Exceptions\UnauthorizedHttpException;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class UnauthorizedResponse extends ResponseFactory {
    public function build(): Response {
        return Response::unauthorized()
            ->description('No authentication was provided or it was invalid')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::string('message')->example(
                                'Unauthenticated.'
                            ),
                            Schema::string('reason')
                                ->description(
                                    "Optional reason:\n- *unverified*: Users email was not yet verified\n- *credentials*: The credentials did not match"
                                )
                                ->enum(...UnauthorizedHttpException::REASONS)
                        )
                        ->required('message')
                )
            );
    }
}
