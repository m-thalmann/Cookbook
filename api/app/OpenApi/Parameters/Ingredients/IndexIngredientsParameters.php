<?php

namespace App\OpenApi\Parameters\Ingredients;

use App\Models\Ingredient;
use App\OpenApi\Parameters\SearchParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexIngredientsParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        $ingredientObject = new Ingredient();

        return [
            ...(new SortParameters(
                $ingredientObject->getSortableProperties()
            ))->build(),
            ...(new SearchParameters(
                $ingredientObject->getSearchProperties()
            ))->build(),
        ];
    }
}

