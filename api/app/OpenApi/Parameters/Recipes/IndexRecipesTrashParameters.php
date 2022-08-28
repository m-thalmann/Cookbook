<?php

namespace App\OpenApi\Parameters\Recipes;

use App\Models\Recipe;
use App\OpenApi\Parameters\FilterParameters;
use App\OpenApi\Parameters\PaginationParameters;
use App\OpenApi\Parameters\SearchParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexRecipesTrashParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        $recipeObject = new Recipe();

        return [
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
        ];
    }
}

