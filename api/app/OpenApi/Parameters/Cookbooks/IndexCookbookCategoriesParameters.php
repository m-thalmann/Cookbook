<?php

namespace App\OpenApi\Parameters\Cookbooks;

use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexCookbookCategoriesParameters extends ParametersFactory {
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

            ...(new SortParameters(['amount']))->build(),
            ...(new BaseParameters())->build(),
        ];
    }
}
