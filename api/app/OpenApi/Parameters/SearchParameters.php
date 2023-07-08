<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class SearchParameters extends ParametersFactory {
    public function __construct(private array $searchColumns = []) {
    }

    /**
     * @return Parameter[]
     */
    public function build(): array {
        $description = 'The value to search by.';

        if (count($this->searchColumns) > 0) {
            $description .= " The following columns are searched:\n";
            $description .= implode(
                "\n",
                array_map(fn($col) => "- $col", $this->searchColumns)
            );
        }

        return [
            Parameter::query()
                ->name('search')
                ->description($description)
                ->required(false)
                ->schema(Schema::string())
                ->example('john'),
        ];
    }
}
