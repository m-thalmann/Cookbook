<?php

namespace App\OpenApi\Parameters\Users;

use App\OpenApi\Parameters\BaseParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class ShowUsersByEmailParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [
            Parameter::path()
                ->name('email')
                ->description('The email of the searched user')
                ->required(true)
                ->schema(Schema::string()),

            ...(new BaseParameters())->build(),
        ];
    }
}
