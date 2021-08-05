<?php

use API\Auth\Authorization;
use API\inc\Functions;
use API\Models\Recipe;

$group->get('/id/{{i:id}}/recipes', Authorization::middleware(false), function (
    $req
) {
    if (
        Authorization::isAuthorized() &&
        Authorization::user()->id === $req["params"]["id"]
    ) {
        $query = Recipe::query("userId = ?", [Authorization::user()->id]);
    } else {
        $query = Recipe::query("public = 1 AND userId = ?", [
            $req["params"]["id"],
        ]);
    }

    return Functions::pagination(Functions::sort($query));
});
