<?php

namespace App\OpenApi;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

abstract class ExtendableSchemaFactory extends SchemaFactory implements
    Reusable {
    public function build(): SchemaContract {
        return Schema::object($this->getName())
            ->properties(...$this->getProperties())
            ->required(...$this->getRequired());
    }

    /**
     * Returns the name of the schema
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Returns the properties for the schema
     *
     * @return array
     */
    abstract public function getProperties(): array;

    /**
     * Returns the keys of the required attributes for the schema
     *
     * @return array
     */
    abstract public function getRequired(): array;
}
