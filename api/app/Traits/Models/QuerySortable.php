<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait QuerySortable {
    /**
     * Returns the properties the query should be sortable by
     *
     * @return string[]
     */
    public function getSortableProperties() {
        if (!isset($this->sortableProperties)) {
            return ['id'];
        }

        return $this->sortableProperties;
    }

    /**
     * Sorts the query using the `sort` query-parameter.
     * It has to have the format: `sort=<prop1>,<prop2>,...`.
     * If a property is prefixed with a `-` it is sorted descending.
     *
     * The properties have to be allowed by the model
     *
     * @see self::getSortableProperties()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param array $extendedProperties Additional properties to allow sorting for this query
     *
     * @return void
     */
    public function scopeSort(
        Builder $query,
        Request $request,
        array $extendedProperties = []
    ) {
        $sortableProperties = $this->getSortableProperties();

        $sortableProperties = array_merge(
            $sortableProperties,
            $extendedProperties
        );

        $sortString = $request->query('sort');

        if ($sortString === null) {
            return;
        }

        foreach (explode(',', $sortString) as $property) {
            $direction = 'asc';

            if (Str::startsWith($property, '-')) {
                $direction = 'desc';
                $property = Str::substr($property, 1);
            }

            if (in_array($property, $sortableProperties)) {
                $query->orderBy($property, $direction);
            }
        }
    }
}
