<?php

namespace API\routes;

use API\auth\Authorization;
use API\inc\Functions;
use API\models\Recipe;
use API\models\RecipeImage;
use API\models\User;

$group->group('/recipes', __DIR__ . '/recipes/index.php');
$group->group('/server', __DIR__ . '/server/index.php');
$group->group('/users', __DIR__ . '/users/index.php');

$group->get('/information', Authorization::middleware(true, true), function () {
    return [
        "users" => [
            "unverified" => User::query("verifyEmailCode IS NOT NULL")->count(),
            "admins" => User::query("isAdmin = 1")->count(),
            "total" => User::query()->count(),
        ],
        "recipes" => [
            "private" => Recipe::query("public = 1")->count(),
            "total" => Recipe::query()->count(),
        ],
        "imagesSize" => Functions::getDirectorySize(
            RecipeImage::getImageStorePath()
        ),
    ];
});
