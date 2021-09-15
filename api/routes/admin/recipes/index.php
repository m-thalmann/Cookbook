<?php

namespace API\routes\admin;

use API\auth\Authorization;
use API\inc\Functions;
use API\models\Recipe;

$group->get('/', Authorization::middleware(true, true), function () {
    $query = "1";
    $queryParameters = [];

    if (!empty($_GET['search'])) {
        $query =
            "(name LIKE :search OR description LIKE :search OR category LIKE :search)";

        $queryParameters["search"] = "%" . urldecode($_GET['search']) . "%";
    }
    if (!empty($_GET['filterUserId'])) {
        if ($query !== "1") {
            $query .= " AND userId = :userId";
        } else {
            $query = "userId = :userId";
        }
        $queryParameters["userId"] = $_GET['filterUserId'];
    }

    return Functions::pagination(
        Functions::sort(
            Recipe::query(
                $query,
                $queryParameters,
                Recipe::FORBIDDEN_SORT_PROPERTIES
            )
        )
    );
});
