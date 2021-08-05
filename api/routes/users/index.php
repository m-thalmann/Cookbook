<?php

use API\Auth\Authorization;
use API\Models\Recipe;

$group->get('/id/{{i:id}}/recipes', Authorization::middleware(false), function (
    $req
) {
    if (
        Authorization::isAuthorized() &&
        Authorization::user()->id === $req["params"]["id"]
    ) {
        return Recipe::query("userId = ?", [
            Authorization::user()->id,
        ])->pagination(
            intval($_GET["items_per_page"] ?? 10),
            intval($_GET["page"] ?? 0)
        );
    } else {
        return Recipe::query("public = 1 AND userId = ?", [
            $req["params"]["id"],
        ])->pagination(
            intval($_GET["items_per_page"] ?? 10),
            intval($_GET["page"] ?? 0)
        );
    }
});
