<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OrganizedBuilder extends EloquentBuilder {
    /**
     * Filters, searches and sorts the query using the request
     *
     * @param Request $request
     *
     * @return $this
     */
    public function organized(Request $request) {
        $this->filter($request);
        $this->search($request);
        $this->sort($request);

        return $this;
    }

    /**
     * Sorts the query using the `sort` query-parameter.
     * It has to have the format: `sort=<prop1>,<prop2>,...`.
     * If a property is prefixed with a `-` it is sorted descending.
     *
     * The properties have to be allowed by the model
     *
     * @see \App\Traits\QueryOrganizable::getSortableProperties()
     *
     * @param Request $request
     * @param array $extendedProperties Additional properties to allow sorting for this query
     *
     * @return $this
     */
    public function sort(Request $request, array $extendedProperties = []) {
        if (!method_exists($this->model, 'getSortableProperties')) {
            return $this;
        }

        $sortableProperties = $this->model->getSortableProperties();

        $sortableProperties = array_merge(
            $sortableProperties,
            $extendedProperties
        );

        $sortString = $request->query('sort');

        if ($sortString === null) {
            return $this;
        }

        foreach (explode(',', $sortString) as $property) {
            $direction = 'asc';

            if (Str::startsWith($property, '-')) {
                $direction = 'desc';
                $property = Str::substr($property, 1);
            }

            if (in_array($property, $sortableProperties)) {
                $this->getQuery()->orderBy($property, $direction);
            }
        }

        return $this;
    }

    /**
     * Filters the query using the `filter` query-parameter(s).
     * It has one of the formats:
     * - `filter[<property>]=<value>`
     * - `filter[<property>][<type>]=<value>`
     *
     * Here the type is one of:
     * - `not`
     * - `like`
     * - `lt` &rArr; `<`
     * - `le` &rArr; `<=`
     * - `ge` &rArr; `>=`
     * - `gt` &rArr; `>`
     *
     * The properties have to be allowed by the model
     *
     * @see \App\Traits\QueryOrganizable::getFilterableProperties()
     *
     * @param Request $request
     * @param array $extendedProperties Additional properties to allow filtering for this query
     *
     * @return $this
     */
    public function filter(Request $request, array $extendedProperties = []) {
        if (!method_exists($this->model, 'getFilterableProperties')) {
            return $this;
        }

        $filterableProperties = $this->model->getFilterableProperties();

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
            return $this;
        }

        foreach ($filters as $filterProperty => $filter) {
            if (!in_array($filterProperty, $filterableProperties)) {
                continue;
            }

            if (is_array($filter) && Arr::isAssoc($filter)) {
                $filterType = array_keys($filter)[0];
                $filterValue = $filter[$filterType];

                if ($filterType === 'not') {
                    $this->whereNot($filterProperty, $filter);
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
                        $this->where($filterProperty, $operator, $filterValue);
                    }
                }
            } else {
                $this->where($filterProperty, $filter);
            }
        }

        return $this;
    }

    /**
     * Searches the query by the value in the `search` query-parameter.
     * It performs a `like` query for each property defined in the
     * search-properties and chains them using `or`.
     *
     * The searched value has to be present somewhere inside the property's value:
     * `prop LIKE '%<search>%'`
     *
     * @see \App\Traits\QueryOrganizable::getSearchProperties()
     *
     * @param Request $request
     * @param array $extendedProperties Additional properties to search for this query
     *
     * @return $this
     */
    public function search(Request $request, array $extendedProperties = []) {
        if (!method_exists($this->model, 'getSearchProperties')) {
            return $this;
        }

        $searchProperties = $this->model->getSearchProperties();

        $searchProperties = array_merge($searchProperties, $extendedProperties);

        $search = $request->query('search');

        if (
            count($searchProperties) === 0 ||
            $search === null ||
            Str::length($search) === 0
        ) {
            return $this;
        }

        $this->where(function ($query) use ($searchProperties, $search) {
            foreach ($searchProperties as $property) {
                $query->orWhere($property, 'LIKE', "%$search%");
            }
        });

        return $this;
    }
}
