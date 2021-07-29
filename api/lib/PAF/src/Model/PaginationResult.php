<?php

namespace PAF\Model;

use PAF\Router\Response;

/**
 * This class provides functionalities for the result of a pagination call
 *
 * @see Query::pagination()
 *
 * @license MIT
 * @author Matthias Thalmann
 */
class PaginationResult implements \JsonSerializable {
    /**
     * @var array Contains the result of the pagination (page, items_per_page, total_items, total_pages, items)
     */
    private $ret = null;

    public function __construct(array $ret) {
        $this->ret = $ret;
    }

    /**
     * Returns the result
     *
     * @return array
     */
    public function get() {
        return $this->ret;
    }

    /**
     * Returns the result in the form of a response, where the response code is 404 (not found),
     * if the items are empty or 200 (ok), if the items are not empty
     *
     * @return Response
     */
    public function getResponse() {
        if (count($this->ret['items']) > 0) {
            return Response::ok($this->ret);
        } else {
            return Response::notFound($this->ret);
        }
    }

    /**
     * @see \JsonSerializable
     *
     * @return array The result
     */
    public function jsonSerialize() {
        return $this->ret;
    }
}
