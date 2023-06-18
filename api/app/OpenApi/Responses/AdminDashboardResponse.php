<?php

namespace App\OpenApi\Responses;

use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class AdminDashboardResponse extends ResponseFactory {
    public function build(): Response {
        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::object('data')
                                ->properties(
                                    Schema::object('api')
                                        ->properties(
                                            Schema::number('version')
                                                ->example(1)
                                                ->description(
                                                    'The current API version'
                                                ),
                                            Schema::string('environment')
                                                ->enum(
                                                    'local',
                                                    'production',
                                                    'demo'
                                                )
                                                ->example('production')
                                                ->description(
                                                    'The current environment'
                                                )
                                        )
                                        ->required('version', 'environment'),

                                    Schema::object('users')
                                        ->properties(
                                            Schema::integer('admin_amount')
                                                ->example(3)
                                                ->description(
                                                    'The amount of admin users'
                                                ),
                                            Schema::integer('total_amount')
                                                ->example(52)
                                                ->description(
                                                    'The total amount of users'
                                                )
                                        )
                                        ->required(
                                            'admin_amount',
                                            'total_amount'
                                        ),

                                    Schema::object('recipes')
                                        ->properties(
                                            Schema::integer('total_amount')
                                                ->example(145)
                                                ->description(
                                                    'The total amount of recipes'
                                                ),
                                            Schema::integer('public_amount')
                                                ->example(52)
                                                ->description(
                                                    'The amount of public recipes'
                                                ),
                                            Schema::integer('private_amount')
                                                ->example(93)
                                                ->description(
                                                    'The amount of private recipes'
                                                )
                                        )
                                        ->required(
                                            'total_amount',
                                            'public_amount',
                                            'private_amount'
                                        ),

                                    Schema::object('cookbooks')
                                        ->properties(
                                            Schema::integer('total_amount')
                                                ->example(11)
                                                ->description(
                                                    'The total amount of cookbooks'
                                                )
                                        )
                                        ->required('total_amount'),

                                    Schema::object('recipe_images')
                                        ->properties(
                                            Schema::integer('total_amount')
                                                ->example(573)
                                                ->description(
                                                    'The total amount of recipe images'
                                                ),

                                            Schema::integer('storage_size')
                                                ->example(1234567890)
                                                ->description(
                                                    'The total storage size of all recipe images in bytes'
                                                )
                                        )
                                        ->required(
                                            'total_amount',
                                            'storage_size'
                                        )
                                )
                                ->required(
                                    'api',
                                    'users',
                                    'recipes',
                                    'cookbooks',
                                    'recipe_images'
                                )
                        )
                        ->required('data')
                )
            );
    }
}
