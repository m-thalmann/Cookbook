<?php

namespace API\routes;

use API\auth\Authorization;
use API\inc\Functions;
use API\models\Recipe;

$group->get("/id/{{i:id}}/recipes", Authorization::middleware(false), function (
    $req
) {
    if (empty($_GET["language"])) {
        $query = Recipe::getQueryForUser(
            "userId = :id",
            [
                "id" => $req["params"]["id"],
            ],
            Authorization::user(),
            false
        );
    } else {
        $query = Recipe::getQueryForUser(
            "userId = :id AND languageCode = :language",
            [
                "id" => $req["params"]["id"],
                "language" => $_GET["language"],
            ],
            Authorization::user(),
            false
        );
    }

    return Functions::pagination(
        Functions::sort($query, Recipe::FORBIDDEN_SORT_PROPERTIES)
    );
});
