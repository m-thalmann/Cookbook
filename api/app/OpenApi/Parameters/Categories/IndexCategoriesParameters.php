<?php

namespace App\OpenApi\Parameters\Categories;

use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexCategoriesParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [
            Parameter::query()
                ->name('all')
                ->description(
                    'Whether to include categories for all recipes visible to the user or only the ones owned by it'
                )
                ->required(false)
                ->allowEmptyValue(true),

            ...(new SortParameters(['amount']))->build(),
        ];
    }
}
