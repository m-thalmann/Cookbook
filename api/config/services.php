<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'hcaptcha' => [
        'secret' => env('HCAPTCHA_SECRET'),
        'enabled' => env('HCAPTCHA_ENABLED', false),
    ],

    'github' => [
        'enabled' => env('OAUTH_GITHUB_ENABLED', false),
        'client_id' => env('OAUTH_GITHUB_CLIENT_ID', null),
        'client_secret' => env('OAUTH_GITHUB_CLIENT_SECRET', null),
        'redirect' =>
            env('APP_URL', 'http://localhost') . '/v1/auth/oauth/github/login',
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
];
