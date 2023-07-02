<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class NotFoundResponse extends ResponseFactory {
    public function build(): Response {
        return Response::notFound()->description('Not found');
    }
}
