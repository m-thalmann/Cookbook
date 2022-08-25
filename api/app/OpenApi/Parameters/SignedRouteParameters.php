<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class SignedRouteParameters extends ParametersFactory {
    public function build(): array {
        return [
            Parameter::query()
                ->name('signature')
                ->description('The url\'s signature')
                ->required(true)
                ->schema(Schema::string()),
            Parameter::query()
                ->name('expires')
                ->description('Timestamp when the signature expires')
                ->required(false)
                ->schema(Schema::integer()),
        ];
    }
}

