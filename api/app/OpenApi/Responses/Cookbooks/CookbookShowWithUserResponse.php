<?php

namespace App\OpenApi\Responses\Cookbooks;

use App\OpenApi\Schemas\CookbookSchema;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Response;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Factories\ResponseFactory;

class CookbookShowWithUserResponse extends ResponseFactory {
    public function build(): Response {
        $cookbookSchema = new CookbookSchema();

        return Response::ok()
            ->description('OK')
            ->content(
                MediaType::json()->schema(
                    Schema::object()
                        ->properties(
                            Schema::object('data')
                                ->properties(
                                    ...[
                                        ...$cookbookSchema->getProperties(),
                                        Schema::object('meta')
                                            ->description(
                                                'Meta information about the user\'s relation'
                                            )
                                            ->properties(
                                                Schema::boolean(
                                                    'is_admin'
                                                )->description(
                                                    'Whether the user is an admin of the cookbook'
                                                ),
                                                Schema::integer('created_at')
                                                    ->description(
                                                        'The unix-timestamp when the user was added to the cookbook'
                                                    )
                                                    ->example(1660997908),
                                                Schema::integer('updated_at')
                                                    ->description(
                                                        'The unix-timestamp when the cookbook-user was last updated'
                                                    )
                                                    ->example(1660997908)
                                            )
                                            ->required(
                                                'is_admin',
                                                'created_at',
                                                'updated_at'
                                            ),
                                    ]
                                )
                                ->required(...$cookbookSchema->getRequired())
                        )
                        ->required('data')
                )
            );
    }
}
