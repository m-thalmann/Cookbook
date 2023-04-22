<?php

namespace App\OpenApi\Schemas;

use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Vyuldashev\LaravelOpenApi\Contracts\Reusable;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class AuthTokenSchema extends SchemaFactory implements Reusable {
    public function build(): SchemaContract {
        return Schema::object('AuthToken')
            ->properties(
                Schema::integer('id')
                    ->description('The id of the token')
                    ->example(1),
                Schema::string('type')
                    ->enum('access', 'refresh')
                    ->description('The type of the token'),
                Schema::string('tokenable_type')
                    ->description('The class for which the token was created')
                    ->default('App\\Models\\User'),
                Schema::integer('tokenable_id')
                    ->description(
                        'The id of the model the token belongs to (the user\'s id'
                    )
                    ->example(1),
                Schema::integer('group_id')
                    ->description(
                        'The token-group this token belongs to (each user has its own groups)'
                    )
                    ->example(2),
                Schema::string('name')
                    ->description('The name of the token')
                    ->example('AccessToken'),
                Schema::array('abilities')
                    ->items(
                        Schema::string()
                            ->description('The ability-string')
                            ->example('*')
                    )
                    ->description('The abilities this token possesses'),
                Schema::string('ip_address')
                    ->nullable()
                    ->description(
                        'The ip-address from which the token was created'
                    ),
                Schema::string('ip_host')
                    ->nullable()
                    ->description(
                        'The ip-host from which the token was created'
                    ),
                Schema::string('user_agent')
                    ->nullable()
                    ->description(
                        'The user-agent from which the token was created'
                    ),
                Schema::object('user_agent_details')
                    ->nullable()
                    ->properties(
                        Schema::string('name')->description(
                            'The name of the browser'
                        ),
                        Schema::string('name_key')->description(
                            'The browser\'s name key'
                        ),
                        Schema::string('os')->description(
                            'The operating system'
                        ),
                        Schema::string('version')->description(
                            'The version of the browser'
                        )
                    )
                    ->required('name', 'name_key', 'os', 'version'),
                Schema::integer('revoked_at')
                    ->nullable()
                    ->description('Unix-timestamp when the token was revoked')
                    ->example(1661440778),
                Schema::integer('expires_at')
                    ->nullable()
                    ->description('Unix-timestamp when the token will expire')
                    ->example(1661440678),
                Schema::integer('created_at')
                    ->description('Unix-timestamp when the token was created')
                    ->example(1661440478),
                Schema::integer('updated_at')
                    ->description(
                        'Unix-timestamp when the token was last updated'
                    )
                    ->example(1661440778),
                Schema::boolean('is_current')->description(
                    'Whether this token is the currently used one'
                )
            )
            ->required(
                'id',
                'type',
                'tokenable_type',
                'tokenable_id',
                'group_id',
                'name',
                'abilities',
                'ip_address',
                'ip_host',
                'user_agent',
                'revoked_at',
                'expires_at',
                'created_at',
                'updated_at',
                'is_current'
            );
    }
}
