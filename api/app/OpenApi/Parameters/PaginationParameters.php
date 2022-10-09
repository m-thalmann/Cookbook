<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class PaginationParameters extends ParametersFactory {
    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [
            Parameter::query()
                ->name('page')
                ->description('The page of the paginated items')
                ->required(false)
                ->schema(Schema::integer()->minimum(1)),

            Parameter::query()
                ->name('per_page')
                ->description('The amount of items per page')
                ->required(false)
                ->schema(Schema::integer()->minimum(1)),
        ];
    }
}

