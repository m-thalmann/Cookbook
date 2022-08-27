<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class SortParameters extends ParametersFactory {
    public function __construct(private array $sortableColumns = []) {
    }

    /**
     * @return Parameter[]
     */
    public function build(): array {
        if (count($this->sortableColumns) > 0) {
            $sortableColumns = [
                ...$this->sortableColumns,
                ...array_map(fn($col) => "-$col", $this->sortableColumns),
            ];

            return self::buildWithSchema(
                Schema::array()->items(
                    Schema::string()->enum(...$sortableColumns)
                )
            );
        } else {
            return self::buildWithSchema(
                Schema::array()->items(Schema::string())
            );
        }
    }

    private static function buildWithSchema(Schema $schema) {
        return [
            Parameter::query()
                ->name('sort')
                ->description(
                    'The comma-separated list of columns to sort by. If a `-` is prepended to a column it is sorted in descending order'
                )
                ->required(false)
                ->explode(false)
                ->schema($schema),
        ];
    }
}

