<?php

return [
    'token_expiration_minutes' => [
        'refresh' => 60 * 24 * 7, // 7 days
        'access' => env('ACCESS_TOKEN_EXPIRATION_MINUTES', 10),
    ],

    'token_prune_after_hours' => [
        'refresh' => 24 * 7 * 2,
        'access' => 24,
    ],
];

