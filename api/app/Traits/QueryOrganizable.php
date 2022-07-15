<?php

namespace App\Traits;

use App\QueryBuilders\OrganizedBuilder;

trait QueryOrganizable {
    /**
     * Returns the properties the query should be sortable by
     *
     * @return string[]
     */
    public function getSortableProperties() {
        if (
            !property_exists($this, 'sortableProperties') ||
            !is_array($this->sortableProperties)
        ) {
            return ['id'];
        }

        return $this->sortableProperties;
    }

    /**
     * Returns the properties the query should be filterable by
     *
     * @return string[]
     */
    public function getFilterableProperties() {
        if (
            !property_exists($this, 'filterableProperties') ||
            !is_array($this->filterableProperties)
        ) {
            return [];
        }

        return $this->filterableProperties;
    }

    /**
     * Returns the properties the query should search
     *
     * @return string[]
     */
    public function getSearchProperties() {
        if (
            !property_exists($this, 'searchProperties') ||
            !is_array($this->searchProperties)
        ) {
            return [];
        }

        return $this->searchProperties;
    }

    public function newEloquentBuilder($query) {
        return new OrganizedBuilder($query);
    }

    /**
     * Begin querying the model.
     *
     * @return \App\QueryBuilders\OrganizedBuilder
     */
    public static function query() {
        return parent::query();
    }
}
