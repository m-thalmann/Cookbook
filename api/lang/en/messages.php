<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Message Language Lines
    |--------------------------------------------------------------------------
    */

    'cant_update_self' => "You can't update yourself",
    'cookbooks' => [
        'cant_delete_last_admin_user' =>
            "You can't delete the last administrator in the cookbook",
        'is_last_admin_user_in_some' =>
            "This user is the last administrator in at least one cookbook. Therefore it can't be deleted.",
    ],
    'database_error' => 'A database error occurred.',
    'email_must_be_verified' => 'You must verify your email.',

    'http' => [
        'route_not_found' => 'API route not found.',
        'too_many_requests' =>
            'Too many requests. Please wait before trying again.',
        'unauthorized' => 'Unauthorized.',
        'invalid_signature' => 'Invalid signature.',
    ],

    'not_available_in_demo' => 'This action is not available in demo mode.',

    'user_already_in_cookbook' => 'The user is already in this cookbook.',
];
