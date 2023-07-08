<?php

namespace App\Traits\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait QueryOrganizable {
    use QuerySortable, QueryFilterable, QuerySearchable;

    /**
     * Filters, searches and sorts the query using the request
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     *
     * @return void
     */
    public function scopeOrganized(Builder $query, Request $request) {
        $this->scopeFilter($query, $request);
        $this->scopeSearch($query, $request);
        $this->scopeSort($query, $request);
    }
}
