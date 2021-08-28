<?php

namespace API\routes;

use API\auth\Authorization;
use API\inc\Functions;
use API\models\Recipe;

$group->get('/id/{{i:id}}/recipes', Authorization::middleware(false), function (
    $req
) {
    $query = Recipe::getQueryForUser(
        "userId = :id",
        [
            "id" => $req["params"]["id"],
        ],
        Authorization::user(),
        false
    );

    return Functions::pagination(Functions::sort($query));
});
