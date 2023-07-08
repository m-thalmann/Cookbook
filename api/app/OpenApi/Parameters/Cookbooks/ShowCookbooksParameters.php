<?php

namespace App\OpenApi\Parameters\Cookbooks;

use App\OpenApi\Parameters\BaseParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class ShowCookbooksParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [
            Parameter::path()
                ->name('cookbook')
                ->description('The cookbook\'s id')
                ->required(true)
                ->schema(Schema::string()),

            ...(new BaseParameters())->build(),
        ];
    }
}
