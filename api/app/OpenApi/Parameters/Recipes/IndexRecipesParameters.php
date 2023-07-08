<?php

namespace App\OpenApi\Parameters\Recipes;

use App\Models\Recipe;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\FilterParameters;
use App\OpenApi\Parameters\PaginationParameters;
use App\OpenApi\Parameters\SearchParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexRecipesParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        $recipeObject = new Recipe();

        return [
            Parameter::query()
                ->name('all')
                ->description(
                    'Whether to include all recipes visible to the user or only the ones owned by it'
                )
                ->required(false)
                ->allowEmptyValue(true),

            Parameter::query()
                ->name('include-deleted')
                ->description(
                    'Whether to include deleted recipes in the response or not (only allowed by the admin)'
                )
                ->required(false)
                ->allowEmptyValue(true),

            ...(new PaginationParameters())->build(),

            ...(new SortParameters(
                $recipeObject->getSortableProperties()
            ))->build(),
            ...(new SearchParameters(
                $recipeObject->getSearchProperties()
            ))->build(),
            ...(new FilterParameters(
                $recipeObject->getFilterableProperties()
            ))->build(),

            ...(new BaseParameters())->build(),
        ];
    }
}
