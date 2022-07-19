<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

trait QuerySearchable {
    /**
     * Returns the properties the query should search
     *
     * @return string[]
     */
    private function getSearchProperties() {
        if (!isset($this->searchProperties)) {
            return [];
        }

        return $this->searchProperties;
    }

    /**
     * Searches the query by the value in the `search` query-parameter.
     * It performs a `like` query for each property defined in the
     * search-properties and chains them using `or`.
     *
     * The searched value has to be present somewhere inside the property's value:
     * `prop LIKE '%<search>%'`
     *
     * @see self::getSearchProperties()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param array $extendedProperties Additional properties to search for this query
     *
     * @return void
     */
    public function scopeSearch(
        Builder $query,
        Request $request,
        array $extendedProperties = []
    ) {
        $searchProperties = $this->getSearchProperties();

        $searchProperties = array_merge($searchProperties, $extendedProperties);

        $search = $request->query('search');

        if (
            count($searchProperties) === 0 ||
            $search === null ||
            Str::length($search) === 0
        ) {
            return;
        }

        $query->where(function ($subQuery) use ($searchProperties, $search) {
            foreach ($searchProperties as $property) {
                $subQuery->orWhere($property, 'LIKE', "%$search%");
            }
        });
    }
}
