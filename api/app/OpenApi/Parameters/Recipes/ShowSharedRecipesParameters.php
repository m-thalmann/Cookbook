<?php

namespace App\OpenApi\Parameters\Recipes;

use App\OpenApi\Parameters\BaseParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class ShowSharedRecipesParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [
            Parameter::path()
                ->name('shareUuid')
                ->description('The recipe\'s share-uuid')
                ->required(true)
                ->schema(Schema::string()),

            ...(new BaseParameters())->build(),
        ];
    }
}
