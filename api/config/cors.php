<?php

use App\Http\Controllers\Auth\AuthenticationController;

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [env('APP_BASE_PATH', '/') . '*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [env('APP_FRONTEND_URL', 'http://localhost/app')],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [AuthenticationController::EMAIL_UNVERIFIED_HEADER],

    'max_age' => 0,

    'supports_credentials' => true,
];
