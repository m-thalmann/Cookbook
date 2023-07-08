<?php

namespace App\OpenApi\Parameters\Cookbooks;

use App\Models\User;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\FilterParameters;
use App\OpenApi\Parameters\PaginationParameters;
use App\OpenApi\Parameters\SearchParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexCookbookUsersParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        $userObject = new User();

        return [
            Parameter::path()
                ->name('cookbook')
                ->description('The cookbook\'s id')
                ->required(true)
                ->schema(Schema::string()),

            ...(new PaginationParameters())->build(),

            ...(new SortParameters(
                $userObject->getSortableProperties()
            ))->build(),
            ...(new SearchParameters(
                $userObject->getSearchProperties()
            ))->build(),
            ...(new FilterParameters(
                $userObject->getFilterableProperties()
            ))->build(),

            ...(new BaseParameters())->build(),
        ];
    }
}
