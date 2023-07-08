<?php

namespace App\OpenApi\Parameters\Auth;

use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\PaginationParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class AuthTokenParameters extends ParametersFactory {
    public function build(): array {
        return [
            Parameter::path()
                ->name('groupId')
                ->description('The id of the group')
                ->required(true)
                ->schema(Schema::string()),

            ...(new PaginationParameters())->build(),
            ...(new BaseParameters())->build(),
        ];
    }
}
