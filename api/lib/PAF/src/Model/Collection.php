<?php

namespace PAF\Model;

use PAF\Router\Response;

/**
 * This class provides functions to modify and traverse a list of model instances
 *
 * @license MIT
 * @author Matthias Thalmann
 */
final class Collection implements
    \IteratorAggregate,
    \Countable,
    \JsonSerializable {
    /**
     * @var array<int, Model> The model instances of the collection
     */
    private $items = null;

    /**
     * Creates an instance of the collection
     *
     * @param array<int, Model> $items The model instances
     */
    public function __construct(array $items) {
        $this->items = $items;
    }

    /**
     * Returns an iterator over the items
     *
     * @see \IteratorAggregate
     *
     * @return \ArrayIterator
     */
    public function getIterator() {
        return new \ArrayIterator($this->items);
    }

    /**
     * Returns the amount of items contained
     *
     * @see \Countable
     *
     * @return int
     */
    public function count() {
        if ($this->items === null) {
            return 0;
        }

        return count($this->items);
    }

    /**
     * Edits the values of all items.
     * The models will not be saved automatically! Use the save()-function
     *
     * @see Collection::save()
     * @see Model::editValues()
     *
     * @param array $values The property name as key and the value as the value to set
     * @param bool $convert Whether the values should be converted before setting them (according to the property type)
     *
     * @return void
     */
    public function editValues(array $values, $convert = false) {
        foreach ($this->items as $item) {
            $item->editValues($values, $convert);
        }
    }

    /**
     * Edits a value of all items.
     * The models will not be saved automatically! Use the save()-function
     *
     * @see Collection::save()
     * @see Model::edit()
     *
     * @param string $property The name of the property
     * @param mixed $value The value to set
     *
     * @return void
     */
    public function edit($property, $value, $convert = false) {
        foreach ($this->items as $item) {
            $item->edit($property, $value, $convert);
        }
    }

    /**
     * Deletes each item from the database
     *
     * @see Model::delete()
     *
     * @return void
     */
    public function delete() {
        foreach ($this->items as $item) {
            $item->delete();
        }
    }

    /**
     * Saves each item to the database
     *
     * @see Model::save()
     *
     * @param bool $reload Whether the instance should be reloaded from the database after completion or not
     *
     * @return void
     */
    public function save($reload = true) {
        foreach ($this->items as $item) {
            $item->save($reload);
        }
    }

    /**
     * Retrieves the n-th item
     *
     * @param int $n The index of the item (starting with 0)
     *
     * @return Model|null The model or null if there is no n-th item
     */
    public function get(int $n) {
        if ($n < $this->count()) {
            return $this->items[$n];
        }

        return null;
    }

    /**
     * Retrieves the first item
     *
     * @see Collection::get()
     *
     * @return Model|null The model or null if there is no first element
     */
    public function getFirst() {
        return $this->get(0);
    }

    /**
     * Returns an array containing all items
     *
     * @return Model[]
     */
    public function toArray() {
        return $this->items;
    }

    /**
     * Creates a response out of the items.
     * If there are no items, the http-status-code will be set to 404 (= not found), otherwise to 200 (= ok)
     *
     * @return Response The created response
     */
    public function getResponse() {
        if ($this->count() > 0) {
            return Response::ok($this->items);
        }

        return Response::notFound([]);
    }

    /**
     * @see \JsonSerializable
     *
     * @return array The items
     */
    public function jsonSerialize() {
        return $this->items;
    }
}
