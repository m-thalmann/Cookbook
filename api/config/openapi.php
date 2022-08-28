<?php

return [
    'collections' => [
        'default' => [
            'info' => [
                'title' => config('app.name'),
                'description' => 'The API for the Cookbook-Webapp',
                'version' => '1.0.0',
                'contact' => [
                    'url' => 'https://github.com/m-thalmann/Cookbook',
                    'email' => 'matthiasthalmann1@hotmail.de',
                    'name' => 'Matthias Thalmann',
                ],
                'license' => [
                    'name' => 'MIT',
                ],
            ],

            'servers' => [
                [
                    'url' => env('APP_URL'),
                    'description' => null,
                    'variables' => [],
                ],
            ],

            'tags' => [
                [
                    'name' => 'Recipes',
                ],
                [
                    'name' => 'Recipe-Images',
                ],
                [
                    'name' => 'Ingredients',
                ],
                [
                    'name' => 'Categories',
                ],
                [
                    'name' => 'Auth',
                ],
                [
                    'name' => 'Auth/Tokens',
                ],
                [
                    'name' => 'Users',
                ],
            ],

            'route' => [
                'uri' => null,
            ],
        ],
    ],
];

