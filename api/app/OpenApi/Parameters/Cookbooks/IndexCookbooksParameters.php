<?php

namespace App\OpenApi\Parameters\Cookbooks;

use App\Models\Cookbook;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\PaginationParameters;
use App\OpenApi\Parameters\SearchParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexCookbooksParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        $cookbookObject = new Cookbook();

        return [
            Parameter::query()
                ->name('all')
                ->description('Whether to include all cookbooks (only admin)')
                ->required(false)
                ->allowEmptyValue(true),

            ...(new PaginationParameters())->build(),

            ...(new SortParameters(
                $cookbookObject->getSortableProperties()
            ))->build(),
            ...(new SearchParameters(
                $cookbookObject->getSearchProperties()
            ))->build(),

            ...(new BaseParameters())->build(),
        ];
    }
}
