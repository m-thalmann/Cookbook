<?php

namespace App\OpenApi\Parameters\Auth;

use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\SignedRouteParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class EmailVerificationParameters extends ParametersFactory {
    public function build(): array {
        return [
            Parameter::path()
                ->name('id')
                ->description('The user\'s id')
                ->required(true)
                ->schema(Schema::string()),
            Parameter::path()
                ->name('hash')
                ->description('The user\'s verification-hash')
                ->required(true)
                ->schema(Schema::string()),

            ...(new SignedRouteParameters())->build(),

            ...(new BaseParameters())->build(),
        ];
    }
}
