<?php

namespace PAF\Model;

use PAF\Router\Response;

/**
 * This class provides a very simple sql query builder for the models.
 *
 * @see Model::query()
 * @see Model::get()
 *
 * @license MIT
 * @author Matthias Thalmann
 */
final class Query {
    /**
     * @var string The class name of the model
     */
    private $modelClass = null;

    /**
     * @var string $whereClause The sql-where-clause for the query (prepared statement)
     * @var array $values The values to insert safely into the query
     */
    private $whereClause = null;
    private $values = [];

    /**
     * @var string[] $orderBy The parts of the order-clause (e.g "username ASC")
     * @var int|null $limit Defines how many results will be returned (default no limit)
     * @var int|null $offset Defines how much the results will be offset (default 0)
     */
    private $orderBy = [];
    private $limit = null;
    private $offset = null;

    /**
     * Creates an instance of the query and checks the class name
     *
     * @see https://phpdelusions.net/pdo#prepared
     *
     * @param string $modelClass The name of the model class
     * @param string $whereClause The sql-where-clause for the query (prepared statement)
     * @param array $values The values to insert safely into the query (see link above)
     *
     * @throws \InvalidArgumentException If the model-class was not found or does not implement PAF\Model\Model
     */
    public function __construct($modelClass, $whereClause, array $values = []) {
        if (!is_string($modelClass) || !class_exists($modelClass)) {
            throw new \InvalidArgumentException('Invalid classname');
        }
        if (!in_array(__NAMESPACE__ . '\\Model', class_parents($modelClass))) {
            throw new \InvalidArgumentException(
                "Invalid class: Must implement '" . __NAMESPACE__ . "\\Model'"
            );
        }

        $this->modelClass = $modelClass;
        $this->whereClause = $whereClause;
        $this->values = $values;
    }

    /**
     * Adds a new part to the order-clause
     *
     * @param string $column The column to order by (has to be the database property name)
     * @param string $direction The order direction ("ASC" or "DESC")
     * @param bool $ignoreError Whether it should ignore an eventual error that is caused by the model not containing the property
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc', $ignoreError = false) {
        $direction = strtoupper($direction);

        if ($direction !== 'DESC' && $direction !== 'ASC') {
            throw new \InvalidArgumentException('Invalid order direction');
        }

        if (!$this->modelClass::hasProperty($column, true)) {
            if (!$ignoreError) {
                throw new \InvalidArgumentException(
                    "'{$this->modelClass}' does not have the property '$column'"
                );
            } else {
                return $this;
            }
        }

        $this->orderBy[] = "$column $direction";

        return $this;
    }

    /**
     * Sets the amount of results returned
     *
     * @param int $amount The amount to be set
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function limit($amount) {
        if (!is_int($amount) || $amount < 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        $this->limit = $amount;
        return $this;
    }

    /**
     * Sets how much the results should be offset
     *
     * @param int $amount The amount to be set
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function offset($amount) {
        if (!is_int($amount) || $amount < 0) {
            throw new \InvalidArgumentException('Invalid amount');
        }

        $this->offset = $amount;
        return $this;
    }

    /**
     * Retrieves the items from the database (using the set where-clause, order-clause and the given
     * constraints) and calculates the total items and pages needed.
     *
     * This function overwrites the set limit and offset properties.
     *
     * The result has the following structure: <br/>
     * ['page'] - int - The current page <br/>
     * ['items_per_page'] - int - The amount of items contained by a page <br/>
     * ['total_items'] - int - The total amount of items found <br/>
     * ['total_pages'] - int - The total amount of pages needed to retrieve all items <br/>
     * ['items'] - Collection/Array - A collection (not raw) / array (raw) containing the resulting items
     *
     * @param int $itemsPerPage The amount of items contained by a page
     * @param int $page The current page (starting with 0)
     *
     * @throws \InvalidArgumentException
     *
     * @return PaginationResult The result class
     */
    public function pagination($itemsPerPage, $page = 0) {
        if (!is_int($itemsPerPage) || $itemsPerPage < 0) {
            throw new \InvalidArgumentException('Invalid items per page');
        }
        if (!is_int($page) || $page < 0) {
            throw new \InvalidArgumentException('Invalid page number');
        }

        $total_items = $this->count();

        $this->offset = $itemsPerPage * $page;
        $this->limit = $itemsPerPage;

        $ret = [
            "page" => $page,
            "items_per_page" => $itemsPerPage,
            "total_items" => $total_items,
            "total_pages" => intval(ceil($total_items / $itemsPerPage)),
            "items" => $this->get(),
        ];

        return new PaginationResult($ret);
    }

    /**
     * Retrieves the raw items from the database (using the set where-clause, order-clause and the given
     * constraints) and calculates the total items and pages needed.
     *
     * This function overwrites the set limit and offset properties.
     *
     * The result has the following structure: <br/>
     * ['page'] - int - The current page <br/>
     * ['items_per_page'] - int - The amount of items contained by a page <br/>
     * ['total_items'] - int - The total amount of items found <br/>
     * ['total_pages'] - int - The total amount of pages needed to retrieve all items <br/>
     * ['items'] - Collection/Array - A collection (not raw) / array (raw) containing the resulting items
     *
     * @param int $itemsPerPage The amount of items contained by a page
     * @param int $page The current page (starting with 0)
     * @param bool $allProperties Whether to return all properties or only the ones where output is true
     *
     * @throws \InvalidArgumentException
     *
     * @return PaginationResult The result class
     */
    public function paginationRaw(
        $itemsPerPage,
        $page = 0,
        $allProperties = true
    ) {
        if (!is_int($itemsPerPage) || $itemsPerPage < 0) {
            throw new \InvalidArgumentException('Invalid items per page');
        }
        if (!is_int($page) || $page < 0) {
            throw new \InvalidArgumentException('Invalid page number');
        }

        $total_items = $this->count();

        $this->offset = $itemsPerPage * $page;
        $this->limit = $itemsPerPage;

        $ret = [
            "page" => $page,
            "items_per_page" => $itemsPerPage,
            "total_items" => $total_items,
            "total_pages" => intval(ceil($total_items / $itemsPerPage)),
            "items" => $this->getRaw($allProperties),
        ];

        return new PaginationResult($ret);
    }

    /**
     * Retrieves the items from the database using the set constraints (where, order, limit, offset)
     *
     * @see Model::get()
     *
     * @return Collection
     */
    public function get() {
        return $this->modelClass::get(
            $this->whereClause,
            $this->values,
            count($this->orderBy) > 0 ? implode(", ", $this->orderBy) : null,
            $this->limit,
            $this->offset
        );
    }

    /**
     * Retrieves the items from the database using the set constraints (where, order, limit, offset)
     * and returns only the values (no models)
     *
     * @see Model::getRaw()
     *
     * @param bool $allProperties Whether to return all properties or only the ones where output is true
     *
     * @return array
     */
    public function getRaw($allProperties = true) {
        return $this->modelClass::getRaw(
            $this->whereClause,
            $this->values,
            count($this->orderBy) > 0 ? implode(", ", $this->orderBy) : null,
            $this->limit,
            $this->offset,
            $allProperties
        );
    }

    /**
     * Returns a response with the Collection (containing the items) as value
     * and the http-status-code set according to the number of items
     *
     * @see Query::get()
     * @see Collection::getResponse()
     *
     * @return Response
     */
    public function getResponse() {
        return $this->get()->getResponse();
    }

    /**
     * Returns a response with the raw values and the http-status-code
     * set according to the number of items
     *
     * @see Query::getRaw()
     *
     * @param bool $allProperties Whether to return all properties or only the ones where output is true
     *
     * @return Response
     */
    public function getRawResponse($allProperties = false) {
        $ret = $this->getRaw($allProperties);

        if (count($ret) > 0) {
            return Response::ok($ret);
        } else {
            return Response::notFound($ret);
        }
    }

    /**
     * Returns the number of rows found for the given where-clause
     *
     * @see Model::count()
     *
     * @return int The number of rows found
     */
    public function count() {
        return $this->modelClass::count($this->whereClause, $this->values);
    }

    /**
     * Deletes the items from the database using the where-clause
     *
     * @see Model::deleteByQuery()
     *
     * @return bool true if it was successful (also if no row deleted), false otherwise
     */
    public function delete() {
        return $this->modelClass::deleteByQuery(
            $this->whereClause,
            $this->values
        );
    }
}
