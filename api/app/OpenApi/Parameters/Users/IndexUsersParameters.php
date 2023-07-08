<?php

namespace App\OpenApi\Parameters\Users;

use App\Models\User;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\FilterParameters;
use App\OpenApi\Parameters\PaginationParameters;
use App\OpenApi\Parameters\SearchParameters;
use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexUsersParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        $userObject = new User();

        return [
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
