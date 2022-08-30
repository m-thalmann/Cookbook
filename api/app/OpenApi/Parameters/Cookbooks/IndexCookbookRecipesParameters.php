<?php

namespace App\OpenApi\Parameters\Cookbooks;

use App\Models\Recipe;
use App\OpenApi\Parameters\FilterParameters;
use App\OpenApi\Parameters\PaginationParameters;
use App\OpenApi\Parameters\SearchParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexCookbookRecipesParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        $recipeObject = new Recipe();

        return [
            Parameter::path()
                ->name('cookbook')
                ->description('The cookbook\'s id')
                ->required(true)
                ->schema(Schema::string()),

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

