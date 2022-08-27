<?php

namespace App\OpenApi\Responses\Users;

use App\OpenApi\Schemas\DetailedUserSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class UserCreatedResponse extends ResponseFactory {
    public function build(): Response {
        return Response::created()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(DetailedUserSchema::ref('data'))
                        ->required('data')
                )
            );
    }
}

