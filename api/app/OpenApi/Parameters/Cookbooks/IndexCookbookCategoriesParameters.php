<?php

namespace App\OpenApi\Parameters\Cookbooks;

use App\OpenApi\Parameters\SortParameters;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class IndexCookbookCategoriesParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [...(new SortParameters(['amount']))->build()];
    }
}
