<?php

namespace App\OpenApi;

interface ExtendableFactory {
    /**
     * Returns the properties for the schema
     *
     * @return array
     */
    public function getProperties(): array;

    /**
     * Returns the keys of the required attributes for the schema
     *
     * @return array
     */
    public function getRequired(): array;
}
