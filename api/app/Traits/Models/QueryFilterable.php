<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

const NULL_CHARACTER = "\x00";

trait QueryFilterable {
    /**
     * Returns the properties the query should be filterable by
     *
     * @return string[]
     */
    private function getFilterableProperties() {
        if (!isset($this->filterableProperties)) {
            return [];
        }

        return $this->filterableProperties;
    }

    /**
     * Filters the query using the `filter` query-parameter(s).
     * It has one of the formats:
     * - `filter[<property>]=<value>` (NULL values encoded as `%00`)
     * - `filter[<property>][<type>]=<value>`
     *
     * Here the type is one of:
     * - `not`
     * - `like`
     * - `in` &rArr; requires a list of comma-separated items
     * - `notin` &rArr; requires a list of comma-separated items
     * - `lt` &rArr; `<`
     * - `le` &rArr; `<=`
     * - `ge` &rArr; `>=`
     * - `gt` &rArr; `>`
     *
     * The properties have to be allowed by the model
     *
     * @see self::getFilterableProperties()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param array $extendedProperties Additional properties to allow filtering for this query
     *
     * @return void
     */
    public function scopeFilter(
        Builder $query,
        Request $request,
        array $extendedProperties = []
    ) {
        $filterableProperties = $this->getFilterableProperties();

        $filterableProperties = array_merge(
            $filterableProperties,
            $extendedProperties
        );

        $filters = $request->query('filter');

        if (
            $filters === null ||
            !is_array($filters) ||
            !Arr::isAssoc($filters)
        ) {
            return;
        }

        foreach ($filters as $filterProperty => $filter) {
            if (!in_array($filterProperty, $filterableProperties)) {
                continue;
            }

            if (is_array($filter) && Arr::isAssoc($filter)) {
                $filterType = array_keys($filter)[0];
                $filterValue = $filter[$filterType];

                if ($filterType === 'not') {
                    if ($filterValue === NULL_CHARACTER) {
                        $query->whereNotNull($filterProperty);
                    } else {
                        $query->whereNot($filterProperty, $filterValue);
                    }
                } elseif ($filterType === 'in') {
                    $list = explode(',', $filterValue);

                    $query->whereIn($filterProperty, $list);
                } elseif ($filterType === 'notin') {
                    $list = explode(',', $filterValue);

                    $query->whereNotIn($filterProperty, $list);
                } else {
                    $operator = null;

                    switch ($filterType) {
                        case 'lt':
                            $operator = '<';
                            break;
                        case 'le':
                            $operator = '<=';
                            break;
                        case 'ge':
                            $operator = '>=';
                            break;
                        case 'gt':
                            $operator = '>';
                            break;
                        case 'like':
                            $operator = 'LIKE';
                            break;
                    }

                    if ($operator !== null) {
                        $query->where($filterProperty, $operator, $filterValue);
                    }
                }
            } else {
                if ($filter === NULL_CHARACTER) {
                    $query->whereNull($filterProperty);
                } else {
                    $query->where($filterProperty, $filter);
                }
            }
        }
    }
}
