<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class TooManyRequestsResponse extends ResponseFactory {
    public function build(): Response {
        return Response::tooManyRequests()->description(
            'Too Many Requests. IP / User locked due to rate-limiting'
        );
    }
}
