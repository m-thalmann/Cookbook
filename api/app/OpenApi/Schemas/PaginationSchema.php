<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

class PaginationSchema {
    /**
     * Returns a pagination-schema with the given schema for the items
     *
     * @param SchemaContract $schema
     *
     * @return SchemaContract
     */
    public static function withDataSchema(SchemaContract $schema) {
        return Schema::object()
            ->properties(
                Schema::array('data')
                    ->items($schema)
                    ->description('The paginated items'),
                Schema::object('meta')
                    ->properties(
                        Schema::integer('count')
                            ->description('The amount of items on this page')
                            ->example(15),
                        Schema::integer('total')
                            ->description('Total amount of items')
                            ->example(200),
                        Schema::integer('per_page')
                            ->description('Amount of items per page')
                            ->example(15),
                        Schema::integer('current_page')
                            ->description('The current page')
                            ->example(12),
                        Schema::integer('last_page')
                            ->description('The number of the last page')
                            ->example(14)
                    )
                    ->required(
                        'count',
                        'total',
                        'per_page',
                        'current_page',
                        'last_page'
                    )
                    ->description('Meta information about the pagination')
            )
            ->required('data', 'meta');
    }
}
