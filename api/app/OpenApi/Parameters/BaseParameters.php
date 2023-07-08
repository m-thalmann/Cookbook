<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class BaseParameters extends ParametersFactory {
    public function __construct(private array $sortableColumns = []) {
    }

    /**
     * @return Parameter[]
     */
    public function build(): array {
        return [
            Parameter::header()
                ->name('X-Language')
                ->description(
                    'Defines the language of the response (as a code)'
                )
                ->required(false)
                ->schema(Schema::string()),

            Parameter::query()
                ->name('lang')
                ->description(
                    'Defines the language of the response (as a code)'
                )
                ->required(false)
                ->schema(Schema::string()),
        ];
    }
}
