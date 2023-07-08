<?php

namespace App\OpenApi\RequestBodies\Ingredients;

use App\OpenApi\ExtendableFactory;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\RequestBodyFactory;

class CreateIngredientRequestBody extends RequestBodyFactory implements
    ExtendableFactory {
    public function build(): RequestBody {
        return RequestBody::create()->content(
            MediaType::json()->schema(
                Schema::object()
                    ->properties(...$this->getProperties())
                    ->required(...$this->getRequired())
            )
        );
    }

    public function getProperties(): array {
        return [
            Schema::string('name')
                ->maxLength(40)
                ->description('The name of the ingredient')
                ->example('Sugar'),
            Schema::number('amount')
                ->minimum(0)
                ->description('The amount of the ingredient to use')
                ->nullable()
                ->example(250),
            Schema::string('unit')
                ->maxLength(20)
                ->description('The unit associated with the amount')
                ->nullable()
                ->example('g'),
            Schema::string('group')
                ->maxLength(20)
                ->description('The ingredient-group this ingredient belongs to')
                ->nullable()
                ->example('Topping'),
            Schema::integer('order_index'),
        ];
    }

    public function getRequired(): array {
        return ['name', 'order_index'];
    }
}
