<?php

namespace App\OpenApi\Parameters;

use GoldSpecDigital\ObjectOrientedOAS\Objects\OneOf;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ParametersFactory;

class FilterParameters extends ParametersFactory {
    public function __construct(private array $filterableColumns = []) {
    }

    /**
     * @return Parameter[]
     */
    public function build(): array {
        if (count($this->filterableColumns) > 0) {
            return self::buildWithColumns($this->filterableColumns);
        } else {
            return self::buildWithSchema(
                Schema::object()->additionalProperties(
                    self::getFilterTypeSchema()
                )
            );
        }
    }

    private static function buildWithColumns(array $filterableColumns) {
        $propertiesSchema = array_map(function ($col) {
            return OneOf::create($col)->schemas(
                self::getFilterTypeSchema(),
                Schema::string()
            );
        }, $filterableColumns);

        return self::buildWithSchema(
            Schema::object()->properties(...$propertiesSchema)
        );
    }

    private static function buildWithSchema(Schema $schema) {
        return [
            Parameter::query()
                ->name('filter')
                ->description(
                    "The column(s) (key) to filter by (value) with optional type of filter as secondary key. Use one of the following:\n" .
                        "- `filter[column]=value`\n" .
                        "- `filter[column][type]=value`\n\n" .
                        "`NULL` is encoded as `%00`'"
                )
                ->required(false)
                ->style('deepObject')
                ->explode(true)
                ->allowReserved(true)
                ->schema($schema),
        ];
    }

    private static function getFilterTypeSchema() {
        return Schema::object()->properties(
            Schema::string('not')->example('Not'),
            Schema::string('like')->example('<SQL-Like string>'),
            Schema::string('in')
                ->description('Comma-separated list of items')
                ->example('1,2,5'),
            Schema::string('notin')
                ->description('Comma-separated list of items')
                ->example('3,4,6'),
            Schema::string('lt')->example('<less than>'),
            Schema::string('le')->example('<less than or equal>'),
            Schema::string('ge')->example('<greater than>'),
            Schema::string('gt')->example('<greater than or equal>')
        );
    }
}

