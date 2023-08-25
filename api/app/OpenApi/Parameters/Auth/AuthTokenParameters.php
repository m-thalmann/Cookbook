<?php

namespace App\OpenApi\Parameters\Auth;

use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\PaginationParameters;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class AuthTokenParameters extends ParametersFactory {
    public function build(): array {
        return [
            ...(new PaginationParameters())->build(),
            ...(new BaseParameters())->build(),
        ];
    }
}
