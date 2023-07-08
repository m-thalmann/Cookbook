<?php

namespace App\OpenApi\Parameters\Recipes;

use App\OpenApi\Parameters\BaseParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class ShowRecipeImagesParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [
            Parameter::path()
                ->name('recipeImage')
                ->description('The recipe-image\'s id')
                ->required(true)
                ->schema(Schema::string()),

            ...(new BaseParameters())->build(),
        ];
    }
}
